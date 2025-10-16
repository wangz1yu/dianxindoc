import { inject } from '@angular/core';
import { tapResponse } from '@ngrx/operators';
import {
  patchState,
  signalStore,
  withComputed,
  withHooks,
  withMethods,
  withState,
} from '@ngrx/signals';
import { rxMethod } from '@ngrx/signals/rxjs-interop';
import { ToastrService } from 'ngx-toastr';
import { debounceTime, distinctUntilChanged, pipe, switchMap, tap } from 'rxjs';
import { toObservable } from '@angular/core/rxjs-interop';
import { CommonError } from '@core/error-handler/common-error';
import { TranslationService } from '@core/services/translation.service';
import { SecurityService } from '@core/security/security.service';
import { ClientService } from './client.service';
import { Client } from '@core/domain-classes/client';

type ClientState = {
  clients: Client[];
  client: Client;
  loadList: boolean;
  isAddUpdate: boolean;
  commonError: CommonError;
};

export const initialClientState: ClientState = {
  clients: [],
  client: null,
  loadList: false,
  isAddUpdate: false,
  commonError: null,
};

export const ClientStore = signalStore(
  { providedIn: 'root' },
  withState(initialClientState),
  withComputed(({ }) => ({})),
  withMethods(
    (
      store,
      clientService = inject(ClientService),
      toastrService = inject(ToastrService),
      translationService = inject(TranslationService)
    ) => ({
      loadClients: rxMethod<void>(
        pipe(
          debounceTime(300),
          switchMap(() =>
            clientService.getClients().pipe(
              tapResponse({
                next: (clients: Client[]) => {
                  patchState(store, {
                    clients: [...clients],
                    loadList: false,
                    isAddUpdate: false,
                    commonError: null,
                  });
                },
                error: (err: CommonError) => {
                  patchState(store, { commonError: err, loadList: false, isAddUpdate: false });
                },
              })
            )
          )
        )
      ),
      deleteClientById: rxMethod<string>(
        pipe(
          distinctUntilChanged(),
          switchMap((clientId: string) =>
            clientService.deleteClient(clientId).pipe(
              tapResponse({
                next: () => {
                  toastrService.success(
                    translationService.getValue('CLIENT_DELETED_SUCCESSFULLY')
                  );
                  patchState(store, {
                    clients: store.clients().filter((w) => w.id !== clientId),
                    loadList: true,
                  });
                },
                error: (err: CommonError) => {
                  patchState(store, { commonError: err, loadList: false });
                },
              })
            )
          )
        )
      ),
      addUpdateClient: rxMethod<Client>(
        pipe(
          distinctUntilChanged(),
          switchMap((client: Client) => {
            if (client.id) {
              return clientService.updateClient(client).pipe(
                tapResponse({
                  next: (response) => {
                    const updatedClient = response as Client;
                    toastrService.success(
                      translationService.getValue('CLIENT_UPDATED_SUCCESSFULLY')
                    );
                    patchState(store, {
                      loadList: true,
                      isAddUpdate: true,
                      clients: store.clients().map((client) => client.id === updatedClient.id ? updatedClient : client),
                    });
                  },
                  error: (err: CommonError) => {
                    patchState(store, { commonError: err, loadList: false });
                  },
                })
              );
            } else {
              return clientService.addClient(client).pipe(
                tapResponse({
                  next: () => {
                    toastrService.success(
                      translationService.getValue('CLIENT_CREATED_SUCCESSFULLY')
                    );
                    patchState(store, {
                      loadList: true,
                      isAddUpdate: true,
                    });
                  },
                  error: (err: CommonError) => {
                    patchState(store, { commonError: err, loadList: false });
                  },
                })
              );
            }
          })
        )
      ),
      getClientById: rxMethod<string>(
        pipe(
          switchMap((clientId: string) =>
            clientService.getClient(clientId).pipe(
              tapResponse({
                next: (client: Client) => {
                  patchState(store, {
                    client: client,
                    commonError: null,
                  });
                },
                error: (err: CommonError) => {
                  patchState(store, { commonError: err, loadList: false });
                },
              })
            )
          )
        )
      ),
      setLoadList: (loadList: boolean) => {
        patchState(store, { loadList: loadList });
      },
    })
  ),

  withHooks({
    onInit(store, securityService = inject(SecurityService)) {
      toObservable(store.loadList).subscribe((flag) => {
        if (flag) {
          store.loadClients();
        }
      });
      if (
        securityService.isLogin()
      ) {
        store.loadClients();
      }
    },
  })
);
