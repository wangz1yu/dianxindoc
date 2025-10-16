import { withStorageSync } from '@angular-architects/ngrx-toolkit';
import { computed, inject } from '@angular/core';
import { patchState, signalStore, withComputed, withHooks, withMethods, withState } from '@ngrx/signals';
import { ToastrService } from 'ngx-toastr';
import { debounceTime, distinctUntilChanged, pipe, switchMap, tap } from 'rxjs';
import { tapResponse } from '@ngrx/operators';
import { Workflow } from '@core/domain-classes/workflow';
import { rxMethod } from '@ngrx/signals/rxjs-interop';
import { CommonError } from '@core/error-handler/common-error';
import { TranslationService } from '@core/services/translation.service';
import { WorkflowStep } from '@core/domain-classes/workflow-step';
import { WorkflowStepService } from './workflow-step.service';
import { WorkflowTransition } from '@core/domain-classes/workflow-transition';
import { WorkflowTransitionService } from './workflow-transition.service';
import { Role } from '@core/domain-classes/role';
import { CommonService } from '@core/services/common.service';
import { User } from '@core/domain-classes/user';
import { WorkflowService } from './workflow-service';

type WorkflowState = {
    workflows: Workflow[];
    commonError: CommonError | null;
    currentWorkflow: Workflow;
    roles: Role[];
    currentStep: number;
    isEditMode: boolean;
    users: User[];
};

export const initialWorkflowState: WorkflowState = {
    workflows: [],
    commonError: null,
    currentWorkflow: null,
    roles: [],
    currentStep: 0,
    isEditMode: false,
    users: []
};

export const WorkflowStore = signalStore(
    { providedIn: 'root' },
    withState(initialWorkflowState),
    withStorageSync({
        key: 'workflows',
        autoSync: true,
        storage: () => sessionStorage,
    }),
    withComputed(({ workflows }) => ({
        sortedWorkflows: computed(() => {
            return workflows().sort((a, b) => a.name.localeCompare(b.name));
        }),
        activeWorkflows: computed(() => {
            return workflows().filter(d => d.isWorkflowSetup).sort((a, b) => a.name.localeCompare(b.name));
        })
    })),
    withMethods(
        (
            store,
            workflowService = inject(WorkflowService),
            workflowTransitionService = inject(WorkflowTransitionService),
            workflowStepService = inject(WorkflowStepService),
            toastrService = inject(ToastrService),
            translationService = inject(TranslationService),
            commonService = inject(CommonService)
        ) => ({
            loadWorkflows: rxMethod<void>(
                pipe(
                    debounceTime(300),
                    switchMap(() =>
                        workflowService.getWorkflows().pipe(
                            tapResponse({
                                next: (workflows: Workflow[]) => {
                                    patchState(store, {
                                        workflows: [...workflows],
                                        commonError: null,
                                    });
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            deleteWorkflowById: rxMethod<string>(
                pipe(
                    distinctUntilChanged(),
                    switchMap((workflowId: string) =>
                        workflowService.deleteWorkflow(workflowId).pipe(
                            tapResponse({
                                next: () => {
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_DELETED_SUCCESSFULLY')
                                    );
                                    patchState(store, {
                                        workflows: store
                                            .workflows()
                                            .filter((w) => w.id !== workflowId),
                                    });
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            addWorkflow: rxMethod<Workflow>(
                pipe(
                    switchMap((workflow: Workflow) =>
                        workflowService.addWorkflow(workflow).pipe(
                            tapResponse({
                                next: (newWorkflow: Workflow) => {
                                    patchState(store, {
                                        workflows: [...store.workflows(), { ...newWorkflow }],
                                        commonError: null,
                                        currentWorkflow: { ...newWorkflow },
                                        currentStep: 1
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_CREATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            updateWorkflow: rxMethod<Workflow>(
                pipe(
                    switchMap((workflow: Workflow) =>
                        workflowService.updateWorkflow(workflow).pipe(
                            tapResponse({
                                next: (updatedWorkflow: Workflow) => {
                                    const currentWorkflow = store.currentWorkflow();
                                    updatedWorkflow.name = updatedWorkflow.name;
                                    updatedWorkflow.description = updatedWorkflow.description;
                                    patchState(store, {
                                        currentWorkflow: { ...currentWorkflow },
                                        workflows: [...store
                                            .workflows().filter((w) => w.id !== updatedWorkflow.id), currentWorkflow
                                        ],
                                        commonError: null,
                                        currentStep: 1
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_UPDATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            getCurrentWorkflow: () => store.currentWorkflow(),
            setCurrentWorkflow: (workFlow: Workflow) => {
                patchState(store, { currentWorkflow: { ...workFlow }, isEditMode: true });
            },
            setError: (commonError: CommonError) => {
                patchState(store, { commonError: { ...commonError } })
            },
            getWorkflowById: rxMethod<string>(
                pipe(
                    switchMap((workflowId: string) =>
                        workflowService.getWorkflow(workflowId).pipe(
                            tapResponse({
                                next: (workflow: Workflow) => {
                                    patchState(store, {
                                        currentWorkflow: workflow,
                                        commonError: null,
                                    });
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            addWorkflowStep: rxMethod<WorkflowStep[]>(
                pipe(
                    switchMap((workflowSteps: WorkflowStep[]) =>
                        workflowStepService.addWorkflowStep(workflowSteps).pipe(
                            tapResponse({
                                next: (newWorkflowStep: WorkflowStep[]) => {
                                    patchState(store, {
                                        currentWorkflow: {
                                            ...store.currentWorkflow(),
                                            workflowSteps: [...newWorkflowStep],
                                        },
                                        currentStep: 2
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_STEP_CREATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            updateWorkflowStep: rxMethod<WorkflowStep[]>(
                pipe(
                    switchMap((workflowSteps: WorkflowStep[]) =>
                        workflowStepService.updateWorkflowStep(workflowSteps).pipe(
                            tapResponse({
                                next: (updateWorkflowStep: WorkflowStep[]) => {
                                    patchState(store, {
                                        currentWorkflow: {
                                            ...store.currentWorkflow(),
                                            workflowSteps: [...updateWorkflowStep],
                                            transitions: [...store.currentWorkflow().transitions],
                                        },
                                        workflows: [...store.workflows().filter((w) => w.id !== updateWorkflowStep[0].workflowId), {
                                            ...store.currentWorkflow(),
                                            workflowSteps: [...updateWorkflowStep],
                                            transitions: [...store.currentWorkflow().transitions]
                                        }],
                                        currentStep: 2
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_STEP_UPDATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            deleteWorkflowStep: rxMethod<string>(
                pipe(
                    switchMap((workflowStepId: string) =>
                        workflowStepService.deleteWorkflowStep(workflowStepId).pipe(
                            tapResponse({
                                next: () => {
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_STEP_DELETED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            getWorkflowStepById: rxMethod<string>(
                pipe(
                    switchMap((workflowStepId: string) =>
                        workflowStepService.getWorkflowStep(workflowStepId).pipe(
                            tapResponse({
                                next: () => {
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            addWorkflowTransition: rxMethod<WorkflowTransition[]>(
                pipe(
                    switchMap((workflowTrnasitions: WorkflowTransition[]) =>
                        workflowTransitionService.addWorkflowTransition(workflowTrnasitions).pipe(
                            tapResponse({
                                next: (newWorkflowTransition: WorkflowTransition[]) => {
                                    patchState(store, {
                                        currentWorkflow: {
                                            ...store.currentWorkflow(),
                                            transitions: [...newWorkflowTransition],
                                        },
                                        currentStep: 0,
                                        commonError: null,
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_TRANSITION_CREATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            updateWorkflowTransition: rxMethod<WorkflowTransition[]>(
                pipe(
                    switchMap((workflowTrnasitions: WorkflowTransition[]) =>
                        workflowTransitionService.updateWorkflowTransition(workflowTrnasitions).pipe(
                            tapResponse({
                                next: (updateWorkflowTransition: WorkflowTransition[]) => {
                                    patchState(store, {
                                        currentWorkflow: {
                                            ...store.currentWorkflow(),
                                            transitions: [...updateWorkflowTransition],
                                        },
                                        currentStep: 0,
                                        commonError: null,
                                    });
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_TRANSITION_UPDATED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            getWorkflowTransitionById: rxMethod<string>(
                pipe(
                    switchMap((workflowTransitionId: string) =>
                        workflowTransitionService.getWorkflowTransition(workflowTransitionId).pipe(
                            tapResponse({
                                next: () => {
                                    patchState(store, {});
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            deleteWorkflowTransition: rxMethod<string>(
                pipe(
                    switchMap((workflowTransitionId: string) =>
                        workflowTransitionService.deleteWorkflowTransition(workflowTransitionId).pipe(
                            tapResponse({
                                next: () => {
                                    toastrService.success(
                                        translationService.getValue('WORKFLOW_TRANSITION_DELETED_SUCCESSFULLY')
                                    );
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            getRoles: rxMethod<void>(
                pipe(
                    switchMap(() =>
                        commonService.getRolesForDropdown().pipe(
                            tapResponse({
                                next: (roles: Role[]) => {
                                    patchState(store, {
                                        roles: [...roles],
                                    });
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            getUsers: rxMethod<void>(
                pipe(
                    switchMap(() =>
                        commonService.getUsersForDropdown().pipe(
                            tapResponse({
                                next: (users: User[]) => {
                                    patchState(store, {
                                        users: [...users],
                                    });
                                },
                                error: (err: CommonError) => {
                                    patchState(store, { commonError: err });
                                    console.error(err);
                                },
                            })
                        )
                    )
                )
            ),
            setCurrentWorkflowAsEmpty: () => {
                patchState(store, {
                    currentWorkflow: {
                        id: '',
                        name: '',
                        description: '',
                        isWorkflowSetup: false,
                        workflowSteps: [],
                        transitions: []
                    },
                    currentStep: 0
                });
            },
            setCurrentStep: (step: number) => {
                patchState(store, {
                    currentStep: step
                });
            },
        })
    ),
    withHooks({
        onInit(store) {
            store.getRoles();
            store.getUsers();
            if (sessionStorage.getItem('workflows')) {
                return;
            }
            store.loadWorkflows();
        },
        onDestroy(store) {
            patchState(store, {
                currentWorkflow: null,
                currentStep: 0
            });
        },
    })
);
