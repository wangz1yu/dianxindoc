import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LayoutComponent } from './layout/app-layout/main-layout/main-layout.component';
import { AuthGuard } from '@core/security/auth.guard';
import { MyProfileComponent } from './user/my-profile/my-profile.component';
import { AppComponent } from './app.component';
import { CompanyProfileResolver } from './company-profile/company-profile.resolver';
import { DocumentLinkPreviewComponent } from '@shared/document-link-preview/document-link-preview.component';
import { ManageWorkflowComponent } from './workflows/manage-workflow/manage-workflow.component';
import { FileRequestLinkPreviewComponent } from './file-request/file-request-preview/file-request-link-preview/file-request-link-preview.component';
import { FileRequestLinkPreviewResolver } from './file-request/file-request-link-preview.resolver';
import { TemplateOpenAiResolverService } from './open-ai/template-openai/template-openai-resolver';
import { documentDetailsResolver } from './document/document-details.resolver';

const routes: Routes = [
  {
    path: '',
    component: AppComponent,
    resolve: { profile: CompanyProfileResolver },
    children: [
      {
        path: 'preview/:code',
        component: DocumentLinkPreviewComponent,
      },
      {
        path: 'error-msg',
        loadComponent: () => import('./error-msg/error-msg.component').then((m) => m.ErrorMsgComponent),
      },
      {
        path: 'activate-license',
        loadComponent: () =>
          import('./activate-license/activate-license.component').then((m) => m.ActivateLicenseComponent),
      },
      {
        path: 'file-requests/preview/:code',
        resolve: { fileRequest: FileRequestLinkPreviewResolver },
        component: FileRequestLinkPreviewComponent,
      },
      {
        path: 'login',
        loadChildren: () =>
          import('./login/login.module').then((m) => m.LoginModule),
      },
      {
        path: 'forgot-password',
        loadChildren: () =>
          import('./forgot-password/forgot-password.module').then(
            (m) => m.ForgotPasswordModule
          ),
      },
      {
        path: 'reset-password',
        loadChildren: () =>
          import('./recover-password/recover-password.module').then(
            (m) => m.RecoverPasswordModule
          ),
      },
      {
        path: 'remove-license-key',
        loadComponent: () =>
          import('./remove-license-key/remove-license-key.component').then(
            (m) => m.RemoveLicenseKeyComponent
          )
      },
      {
        path: '',
        component: LayoutComponent,
        children: [
          {
            path: '',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./document-library/document-library.module').then(
                (m) => m.DocumentLibraryModule
              ),
          },
          {
            path: 'my-profile',
            component: MyProfileComponent,
            canActivate: [AuthGuard],
          },
          {
            path: 'dashboard',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./dashboard/dashboard.module').then(
                (m) => m.DashboardModule
              ),
          },
          {
            path: 'pages',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./page/page.module').then((m) => m.PageModule),
          },
          {
            path: 'roles',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./role/role.module').then((m) => m.RoleModule),
          },
          {
            path: 'users',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./user/user.module').then((m) => m.UserModule),
          },
          {
            path: 'categories',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./category/category.module').then(
                (m) => m.CategoryModule
              ),
          },
          {
            path: 'documents',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./document/document.module').then(
                (m) => m.DocumentModule
              ),
          },
          {
            path: 'document-audit-trails',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./document-audit-trail/document-audit-trail.module').then(
                (m) => m.DocumentAuditTrailModule
              ),
          },
          {
            path: 'login-audit',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./login-audit/login-audit.module').then(
                (m) => m.LoginAuditModule
              ),
          },
          {
            path: 'notifications',
            canLoad: [AuthGuard],
            loadChildren: () =>
              import('./notification/notification.module').then(
                (m) => m.NotificationModule
              ),
          },
          {
            path: 'reminders',
            loadChildren: () =>
              import('./reminder/reminder.module').then(
                (m) => m.ReminderModule
              ),
          },
          {
            path: 'email-smtp',
            loadChildren: () =>
              import('./email-smtp-setting/email-smtp-setting.module').then(
                (m) => m.EmailSmtpSettingModule
              ),
          },
          {
            path: 'company-profile',
            loadChildren: () =>
              import('./company-profile/company-profile.module').then(
                (m) => m.CompanyProfileModule
              ),
          },
          {
            path: 'allow-file-extension',
            canActivate: [AuthGuard],
            data: { claimType: 'SETTINGS_MANAGE_ALLOW_FILE_EXTENSIONS' },
            loadComponent: () =>
              import('./allow-file-extension/allow-file-extension-list/allow-file-extension-list.component').then(
                (m) => m.AllowFileExtensionListComponent
              ),
          },
          {
            path: 'allow-file-extension/manage',
            data: { claimType: 'SETTINGS_MANAGE_ALLOW_FILE_EXTENSIONS' },
            canActivate: [AuthGuard],
            loadChildren: () =>
              import('./allow-file-extension/allow-file-extension-routes').then(
                (m) => m.ALLOW_FILE_EXTENSION_ROUTES
              ),
          },
          {
            path: 'client',
            canActivate: [AuthGuard],
            data: { claimType: 'CLIENTS_MANAGE_CLIENTS' },
            loadComponent: () =>
              import('./client/client-list/client-list.component').then(
                (m) => m.ClientListComponent
              ),
          },
          {
            path: 'client/manage',
            data: { claimType: ['CLIENTS_MANAGE_CLIENTS'] },
            canActivate: [AuthGuard],
            loadChildren: () =>
              import('./client/client-routes').then(
                (m) => m.CLIENT_ROUTES
              ),
          },
          {
            path: 'file-request',
            data: { claimType: 'FILE_REQUEST_VIEW_FILE_REQUEST' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./file-request/file-request-list/file-request-list.component').then(
                (m) => m.FileRequestListComponent
              ),
          },
          {
            path: 'file-request/manage',
            data: { claimType: ['FILE_REQUEST_CREATE_FILE_REQUEST', 'FILE_REQUEST_UPDATE_FILE_REQUEST'] },
            canActivate: [AuthGuard],
            loadChildren: () =>
              import('./file-request/file-request-routes').then(
                (m) => m.FILE_REQUEST_ROUTES
              ),
          },
          {
            path: 'languages',
            loadChildren: () =>
              import('./languages/languages.module').then(
                (m) => m.LanguagesModule
              ),
          },
          {
            path: 'archived-documents',
            loadChildren: () =>
              import('./archived-document/archived-document.module').then(
                (c) => c.ArchivedDocumentModule
              ),
          },
          {
            path: 'page-helper',
            loadChildren: () =>
              import('./page-helper/page-helper.module').then(
                (c) => c.PageHelperModule
              ),
          },
          {
            path: 'bulk-document-upload',
            data: { claimType: 'BULK_DOCUMENT_UPLOAD' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./bulk-document-upload/bulk-document-upload.component').then(
                (m) => m.BulkDocumentUploadComponent
              ),
          },
          {
            path: 'document-status',
            data: { claimType: 'MANAGE_DOCUMENT_STATUS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./document-status/document-status-list/document-status-list.component').then(
                (m) => m.DocumentStatusListComponent
              ),
          },
          {
            path: 'ai-template',
            data: { claimType: 'MANAGE_AI_PROMPT_TEMPLATES' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./open-ai/template-openai/template-openai-list/template-openai-list.component').then(
                (m) => m.TemplateOpenaiListComponent
              ),
          },
          {
            path: 'ai-template/:id',
            data: { claimType: 'MANAGE_AI_PROMPT_TEMPLATES' },
            canActivate: [AuthGuard],
            resolve: {
              aIPromptTemplate: TemplateOpenAiResolverService,
            },
            loadComponent: () =>
              import('./open-ai/template-openai/template-openai.component').then(
                (m) => m.TemplateOpenaiComponent
              ),
          },
          {
            path: 'ai-document-generator-list',
            data: { claimType: 'VIEW_AI_GENERATED_DOCUMENTS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./open-ai/ai-document-generator-list/ai-document-generator-list.component').then(
                (m) => m.AiDocumentGeneratorListComponent
              ),
          },
          {
            path: 'ai-document-generator',
            data: { claimType: 'GENERATE_AI_DOCUMENTS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./open-ai/ai-document-generator/ai-document-generator.component').then(
                (m) => m.AiDocumentGeneratorComponent
              ),
          },
          {
            path: 'document-details/:id',
            loadComponent: () => import('./document/document-details/document-details.component').then((m) => m.DocumentDetailsComponent),
            data: { claimType: ['ALL_DOCUMENTS_VIEW_DETAIL', 'ASSIGNED_DOCUMENTS_VIEW_DETAIL'] },
            canActivate: [AuthGuard],
            resolve: {
              document: documentDetailsResolver,
            },
          },
          {
            path: 'current-workflow',
            data: { claimType: 'WORKFLOW_VIEW_MY_WORKFLOWS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./workflows/manage-current-workflow/manage-current-workflow.component').then(
                (m) => m.ManageCurrentWorkflowComponent
              ),
          },
          {
            path: 'workflow-settings',
            data: { claimType: 'WORKFLOW_VIEW_WORKFLOW_SETTINGS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./workflows/workflow-list/workflow-list.component').then(
                (m) => m.WorkflowListComponent
              ),
          },
          {
            path: 'workflows',
            data: { claimType: ['WORKFLOW_VIEW_ALL_WORKFLOWS'] },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./workflows/manage-all-workflow/manage-all-workflow.component').then(
                (m) => m.ManageAllWorkflowComponent
              ),
          },
          {
            path: 'workflow-settings/manage',
            data: { claimType: ['WORKFLOW_ADD_WORKFLOW', 'WORKFLOW_UPDATE_WORKFLOW', 'WORKFLOW_DELETE_WORKFLOW'] },
            canActivate: [AuthGuard],
            component: ManageWorkflowComponent,
            loadChildren: () =>
              import('./workflows/manage-workflow/manage-workflow-routes').then(
                (m) => m.WORKFLOW_ROUTES
              ),
          }, {
            path: 'workflow-logs',
            data: { claimType: 'WORKFLOW_VIEW_WORKFLOW_LOGS' },
            canActivate: [AuthGuard],
            loadComponent: () =>
              import('./workflows/workflow-logs/workflow-logs.component').then(
                (m) => m.WorkflowLogsComponent
              ),
          },
          {
            path: 'cron-job-logs',
            loadComponent: () => import('./cron-job-logs/cron-job-logs.component').then((m) => m.CronJobLogsComponent),
            data: { claimType: 'LOGS_VIEW_CRON_JOBS_LOGS' },
            canActivate: [AuthGuard]
          },
          {
            path: 'email-logs',
            loadComponent: () => import('./email-logs/email-logs.component').then((m) => m.EmailLogsComponent),
            data: { claimType: 'LOGS_VIEW_EMAIL_LOGS' },
            canActivate: [AuthGuard]
          }, {
            path: 'expired-documents',
            loadComponent: () =>
              import('./expired-document/expired-document.component').then(
                (c) => c.ExpiredDocumentComponent
              ),
            data: { claimType: 'EXPIRED_DOCUMENTS_VIEW_DOCUMENT' },
            canActivate: [AuthGuard]
          },

          {
            path: '**',
            redirectTo: '/',
          },
        ],
      },
    ],
  },
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {
      scrollPositionRestoration: 'top',
      useHash: false,
    }),
  ],
  exports: [RouterModule],
})
export class AppRoutingModule { }
