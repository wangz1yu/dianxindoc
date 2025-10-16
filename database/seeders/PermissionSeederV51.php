<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\PageHelper;
use App\Models\Pages;
use App\Models\RoleClaims;
use App\Models\UserNotifications;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Users;

class PermissionSeederV51 extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runOnce(function () {
            $systemUser = Users::withoutGlobalScope('isSystemUser')->where('isSystemUser', true)->first();
            $user = Users::first();

            if (!$systemUser) {
                Users::create([
                    'id' => (string) Str::uuid(),
                    'firstName' => 'System',
                    'lastName' => 'User',
                    'email' => 'system@user.com',
                    'password' => bcrypt('password'),
                    'isSystemUser' => true,
                ]);
            }

            $cronJobPageHelper = PageHelper::where('code', 'CRON_JOB_LOGS')->first();
            if (!$cronJobPageHelper) {
                PageHelper::create([
                    'id' => 'e22c0cdf-9f03-4e08-abe1-3b6a3c17dd09',
                    'code' => 'CRON_JOB_LOGS',
                    'name' =>
                    'Cron Job Logs',
                    'description' => '<h2>📋 <strong>Cron Job Logs – User Guide</strong></h2><p>The <strong>Cron Job Logs</strong> page provides a detailed overview of all automated background tasks scheduled and executed by the system. These tasks help maintain, notify, and manage document workflows, reminders, and data retention policies without manual effort. This guide explains each job and its purpose to help users understand the system’s automation process.</p><h3>🔄 <strong>List of Scheduled Cron Jobs</strong></h3><h4>📅 <strong>Custom Date Reminder</strong></h4><p>Sends reminders based on user-defined custom dates set for documents or events. Ideal for special dates that don’t follow standard reminder frequencies.</p><h4>🗓️ <strong>Daily Notification Handler</strong></h4><p>Processes and sends out daily notifications to users, keeping them informed about document activities, pending actions, or scheduled events.</p><h4>🧹 <strong>Delete Archive Document By Retention Date</strong></h4><p>Automatically deletes archived documents that have exceeded their configured retention period to comply with data retention policies.</p><h4>🧾 <strong>Delete Email, Audit and Cron Job Logs</strong></h4><p>Cleans up old logs including email history, audit trails, and cron job executions after a defined period to ensure system performance and storage optimization.</p><h4>🗃️ <strong>Delete or Archive or Expire Document By Retention Date</strong></h4><p>Manages documents based on their retention configuration—automatically <strong>deleting</strong>, <strong>archiving</strong>, or <strong>expiring</strong> them as per rules defined in the retention settings.</p><h4>📆 <strong>Half Yearly Reminder</strong></h4><p>Sends notifications every six months to remind users of document reviews, renewals, or other periodic tasks.</p><h4>📅 <strong>Monthly Reminder</strong></h4><p>Sends monthly email reminders to assigned users or groups to take action on documents that require periodic attention.</p><h4>🗓️ <strong>Quarterly Reminder</strong></h4><p>Triggers every three months to notify users of scheduled document-related tasks or to prompt reviews.</p><h4>🛎️ <strong>Notification Scheduler</strong></h4><p>Central handler that coordinates all scheduled notifications (daily, weekly, etc.) to ensure they are sent at the right time with the right content.</p><h4>📤 <strong>Send Email</strong></h4><p>Responsible for sending emails queued by other jobs (like reminders or notifications). Ensures reliable and trackable delivery of communication from the system.</p><h4>📆 <strong>Weekly Reminder</strong></h4><p>Sends weekly emails about document actions, expiring records, or general reminders to keep users updated.</p><h4>🗓️ <strong>Yearly Reminder</strong></h4><p>Annual notifications for documents that require yearly reviews, renewals, or actions—commonly used for compliance or regulatory tasks.</p><h3>✅ <strong>Usage Tips</strong></h3><ul><li><strong>Monitor Execution</strong>: Check if each cron job is executing successfully or failing. Failures may require admin attention.</li><li><strong>Filter Logs</strong>: Use available filters to find logs by job name, status, or date.</li><li><strong>Audit Trail</strong>: This page acts as an audit trail for system automation—valuable for compliance reviews and troubleshooting.</li><li><strong>Retention Settings</strong>: Ensure retention rules are properly configured, as they directly influence deletion and archival jobs.</li></ul>'
                ]);
            }

            $emailLogPageHelper = PageHelper::where('code', 'EMAIL_LOGS')->first();
            if (!$emailLogPageHelper) {
                PageHelper::create([
                    'id' => '703364a0-7df2-468f-a10e-85a95c171377',
                    'code' => 'EMAIL_LOGS',
                    'name' => 'Email Logs',
                    'description' =>  '<p><strong>Email Logs</strong> in a <strong>Document management system</strong> help monitor and track all email communications sent through the system. This feature ensures transparency, enables debugging in case of errors, and provides a history of email activity for auditing purposes.</p><hr><h3><strong>Key Features of Email Logs</strong></h3><ol><li><p><strong>Basic Email Details</strong></p><ul><li><p><strong>Email ID</strong>: A unique identifier for each email sent.</p></li><li><p><strong>Timestamp</strong>: Date and time the email was sent.</p></li><li><p><strong>Sender Email Address</strong>: The email address from which the email was sent (e.g., <a href="mailto:support@yourbusiness.com">support@</a><a href="yourbusiness.com" target="_blank">yourbusiness.com</a>).</p></li><li><p><strong>Recipient Email Address</strong>: The email address of the recipient.</p></li></ul></li><li><p><strong>Email Content Details</strong></p><ul><li><p><strong>Subject</strong>: The subject line of the email.</p></li><li><p><strong>Body</strong>: A preview or complete content of the email.</p></li><li><p><strong>Attachments</strong>: Details of any files attached to the email (e.g., invoices, purchase orders).</p></li></ul></li><li><p><strong>Delivery Status</strong></p><ul><li><p><strong>Status</strong>: The status of the email (e.g., Sent, Failed, Queued, Delivered, Opened, Bounced).</p></li><li><p><strong>Error Details</strong>: If the email failed, the error message or code explaining why (e.g., invalid recipient address, server timeout).</p></li></ul></li></ol><hr><h3><strong>How to Implement Email Logs in a Document management System</strong></h3><ol><li><p><strong>Email Sending Service Integration</strong></p><ul><li><p>Integrate with an SMTP server, third-party email service (e.g., SendGrid, Mailgun), or a built-in email module.</p></li></ul></li><li><p><strong>Database for Logging</strong></p><ul><li><p>Store email logs in a dedicated database table with all relevant fields (email ID, recipient, status, etc.).</p></li></ul></li><li><p><strong>UI for Logs</strong></p><ul><li><p>Design a user-friendly interface to view, filter, and export email logs.</p></li></ul></li><li><p><strong>Error Handling</strong></p><ul><li><p>Implement robust error-catching mechanisms to record and display reasons for failures.</p></li></ul></li><li><p><strong>Automated Notifications</strong></p><ul><li><p>Set up automatic alerts for critical email delivery issues.</p></li></ul></li></ol><hr><p></p>'
                ]);
            }

            $retensionPageHelper = PageHelper::where('code', 'ARCHIVE_DOCUMENT_RETENTION_PERIOD')->first();
            if (!$retensionPageHelper) {
                PageHelper::create([
                    'id' => '6dd7fdb8-efa2-46f7-ae22-ac24630c31c0',
                    'code' => 'ARCHIVE_DOCUMENT_RETENTION_PERIOD',
                    'name' => 'Archive Document Retention Period',
                    'description' => '<p><strong>What is it?</strong><br>Archive Retention Period allows you to automatically move documents to the <strong>delete</strong> after a selected number of days.</p><p><strong>Retention Options:</strong><br>You can choose to automatically delete documents after:</p><p>30 days</p><p>60 days</p><p>90 days</p><p>180 days</p><p>365 days</p><p><strong>How it works:</strong><br>Once this setting is enabled:</p><p>The system will monitor the age of each document.</p><p>When a document reaches the selected retention period (e.g., 30 days), it will be <strong>automatically deleted</strong>.</p><p><i>Enabling this feature helps keep your workspace organized by removing old documents automatically.</i></p>'
                ]);
            }

            $logAction = Actions::where('code', 'LOGS_VIEW_CRON_JOBS_LOGS')->first();
            if (!$logAction) {

                $logPage = Pages::find('f042bbee-d15f-40fb-b79a-8368f2c2e287');
                if ($logPage) {
                    $logPage->update([
                        'name' => 'Logs',
                        'order' => 10,
                        'modifiedBy' => $user->id,
                        'isDeleted' => 0
                    ]);
                }

                $actionsToAdd = [
                    ['id' => 'a74e0f79-bc3c-4582-a2ea-008d568e6a8b', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'View Cron Job Logs', 'order' => 2, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'LOGS_VIEW_CRON_JOBS_LOGS'],
                    ['id' => '1e5fc904-5f70-4b07-8914-242703da5702', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'View Email Logs', 'order' => 3, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'LOGS_VIEW_EMAIL_LOGS'],
                    ['id' => '538c081b-2e14-4f0d-bc34-5f26ad2f77cf', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'Delete Email Log', 'order' => 4, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'LOGS_DELETE_EMAIL_LOG'],
                    ['id' => '322c388d-0ab4-4617-9bee-a8c79906e738', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'Update Archive Document Settings', 'order' => 4, 'pageId' => '05edb281-cddb-4281-9ab3-fb90d1833c82', 'code' =>  'ARCHIVE_DOCUMENT_SET_RETENTION_PERIOD'],
                    ['id' => '2f264576-2d7f-44a2-beeb-97c53847ad70', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'Update Email Log Settings', 'order' => 5, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'EMAIL_LOG_SET_RETENTION_PERIOD'],
                    ['id' => '9ac0f6f5-0731-49d9-a7b9-6fbd92291241', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'Update Cron Job Log Settings', 'order' => 6, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'CRON_JOB_LOG_SET_RETENTION_PERIOD'],
                    ['id' => '324192f0-a319-4228-ba06-f1ce10189822', 'createdBy' => $user->id, 'modifiedBy' => $user->id, 'name' => 'Update Login Audit Settings', 'order' => 7, 'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287', 'code' =>  'LOGIN_AUDIT_SET_RETENTION_PERIOD']
                ];

                $roleClaims = [
                    ['id' => Str::uuid(36), 'actionId' => 'a74e0f79-bc3c-4582-a2ea-008d568e6a8b', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'LOGS_VIEW_CRON_JOBS_LOGS', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '1e5fc904-5f70-4b07-8914-242703da5702', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'LOGS_VIEW_EMAIL_LOGS', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '538c081b-2e14-4f0d-bc34-5f26ad2f77cf', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'LOGS_DELETE_EMAIL_LOG', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '322c388d-0ab4-4617-9bee-a8c79906e738', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'ARCHIVE_DOCUMENT_SET_RETENTION_PERIOD', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '2f264576-2d7f-44a2-beeb-97c53847ad70', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'EMAIL_LOG_SET_RETENTION_PERIOD', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '9ac0f6f5-0731-49d9-a7b9-6fbd92291241', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'CRON_JOB_LOG_SET_RETENTION_PERIOD', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '324192f0-a319-4228-ba06-f1ce10189822', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'LOGIN_AUDIT_SET_RETENTION_PERIOD', 'claimValue' => '']
                ];

                Actions::insert($actionsToAdd);
                RoleClaims::insert($roleClaims);
            }

            $signatureAction = Actions::where('code', 'ALL_DOC_ADD_SIGNATURE')->first();
            if (!$signatureAction) {
                $signatureActions = [[
                    'id' => 'f5829228-ea73-4389-8aee-e2dc8ef6934a',
                    'createdBy' => $user->id,
                    'modifiedBy' => $user->id,
                    'name' => 'Add Signature',
                    'order' => 5,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOC_ADD_SIGNATURE'
                ], [
                    'id' => 'aa712002-aa9a-4656-9835-34278487a848',
                    'createdBy' => $user->id,
                    'modifiedBy' => $user->id,
                    'name' => 'Add Signature',
                    'order' => 5,
                    'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                    'code' => 'ASSIGN_ADD_SIGNATURE'
                ], [
                    'id' => '5f7c13fd-3c5d-4e69-9e21-a263924d273b',
                    'createdBy' => $user->id,
                    'modifiedBy' => $user->id,
                    'name' => 'Change PDF Signature Settings',
                    'order' => 6,
                    'pageId' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2',
                    'code' => 'SETTINGS_CHANGE_PDF_SETTINGS'
                ]];

                $roleClaimsToAdd = [
                    ['id' => Str::uuid(36), 'actionId' => 'f5829228-ea73-4389-8aee-e2dc8ef6934a', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'ALL_DOC_ADD_SIGNATURE', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => 'aa712002-aa9a-4656-9835-34278487a848', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'ASSIGN_ADD_SIGNATURE', 'claimValue' => ''],
                    ['id' => Str::uuid(36), 'actionId' => '5f7c13fd-3c5d-4e69-9e21-a263924d273b', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'SETTINGS_CHANGE_PDF_SETTINGS', 'claimValue' => '']
                ];

                $pageHelpers = [[
                    'id' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2',
                    'code' => 'DOCUMENT_SIGNATURE',
                    'name' => 'DOCUMENT_SIGNATURE',
                    'description' => '<p><strong>Document Signature Functionality</strong></p><p>The <strong>Document Signature</strong> feature allows users to digitally sign documents with ease. This functionality is designed to make the process simple, secure, and efficient, eliminating the need for printing and manual signatures.</p><h3>How It Works:</h3><ol><li><strong>Initiating the Signature Process:</strong><ul><li>Users can click on the <strong>"Document Signature"</strong> button for any document.</li><li>A <strong>popup window</strong> opens, providing options to add a signature.</li></ul></li><li><strong>Applying the Signature:</strong><ul><li>Users can <strong>draw</strong> their signature using a touchscreen or mouse.</li><li>Alternatively, they can <strong>type</strong> their name and choose from various font styles to create a professional-looking signature.</li><li>The signature can be placed anywhere on the document by dragging it to the desired location.</li></ul></li><li><strong>Additional Functionalities:</strong><ul><li><strong>PDF Signature Integration:</strong> Users can directly sign PDFs without converting file formats.</li><li>The <strong>Company Profile</strong> section allows users to include their company details, such as &nbsp;in the PDF signature.</li></ul></li></ol><h3>Key Features:</h3><ul><li><strong>Interactive and User-Friendly:</strong> The popup makes it easy to apply signatures in just a few clicks.</li><li><strong>Professional Branding:</strong> Integrate company details with your signature for added authenticity.</li><li><strong>Secure Signing:</strong> Digital signatures are encrypted to ensure document integrity.</li><li><strong>Flexibility:</strong> Customize the signature and include additional annotations like dates or initials.</li></ul><h3>Benefits:</h3><ul><li><strong>Streamlined Workflow:</strong> Quickly sign and finalize documents without printing or scanning.</li><li><strong>Enhanced Professionalism:</strong> Signatures with company branding make documents look polished and credible.</li><li><strong>Secure and Reliable:</strong> All signed documents are protected with advanced encryption to ensure they remain tamper-proof.</li></ul><p>With the <strong>Document Signature</strong> feature, signing documents becomes fast, professional, and secure, offering users the flexibility and tools they need to manage their documents seamlessly.</p>'
                ], [
                    'id' => 'b3dc6a76-6bd3-46ec-956c-eccef2df3eec',
                    'code' => 'EXPIRED_DOCUMENTS',
                    'name' => 'Expired Documents',
                    'description' =>  '<p><strong>Expired Documents</strong> in a <strong>Document Management System</strong> automatically track and highlight files that have passed their defined expiration time. This helps maintain compliance, improves organization, and supports timely document lifecycle management.</p><h3>Key Features:</h3><ul><li><strong>Automated Tracking:</strong> The system automatically identifies and marks documents as expired based on predefined criteria.</li><li><strong>Notifications:</strong> Users receive alerts for expired documents, ensuring timely review and action.</li><li><strong>Reporting:</strong> Generate reports on expired documents for compliance and auditing purposes.</li></ul><h3>Benefits:</h3><ul><li><strong>Improved Compliance:</strong> Stay compliant with regulations by managing document lifecycles effectively.</li><li><strong>Enhanced Organization:</strong> Keep your document repository tidy by regularly reviewing and archiving expired files.</li><li><strong>Increased Efficiency:</strong> Reduce the risk of using outdated documents in critical processes.</li></ul>'
                ]];

                Actions::insert($signatureActions);
                RoleClaims::insert($roleClaimsToAdd);
                PageHelper::insert($pageHelpers);

                // Update existing user notifications to set notificationType to 1 if documentId is null
                UserNotifications::whereNull('documentId')->update(['notificationType' => 1]);
            }

            $expiredDocumentPage = Pages::where('name', '=', 'Expired Documents')->first();
            if ($expiredDocumentPage == null) {

                $expiredPage = [
                    'id' => 'c78e8ff2-71d7-49e4-bbee-a71ef9d581e9',
                    'name' => 'Expired Documents',
                    'order' => 6,
                    'createdBy' => $user->id,
                    'modifiedBy' => $user->id,
                    'isDeleted' => 0
                ];

                $expiredDocumentActions = [
                    [
                        'id' => '5f24c3d8-94d8-4e57-adb3-bef3e000e7d0',
                        'name' => 'View Expired Documents',
                        'order' => 1,
                        'pageId' => 'c78e8ff2-71d7-49e4-bbee-a71ef9d581e9',
                        'code' => 'EXPIRED_DOCUMENTS_VIEW_DOCUMENT',
                        'createdBy' => $user->id,
                        'modifiedBy' => $user->id,
                        'isDeleted' => 0
                    ],
                    [
                        'id' => '31ba8e74-8fa0-4c34-82ac-950e73a4c18e',
                        'name' => 'Activate Document',
                        'order' => 1,
                        'pageId' => 'c78e8ff2-71d7-49e4-bbee-a71ef9d581e9',
                        'code' => 'EXPIRED_DOCUMENTS_ACTIVATE_DOCUMENT',
                        'createdBy' => $user->id,
                        'modifiedBy' => $user->id,
                        'isDeleted' => 0
                    ],
                    [
                        'id' => '086ce19f-5f1b-42ec-98ac-dea2d92901a3',
                        'name' => 'Archive Document',
                        'order' => 1,
                        'pageId' => 'c78e8ff2-71d7-49e4-bbee-a71ef9d581e9',
                        'code' => 'EXPIRED_DOCUMENTS_ARCHIVE_DOCUMENT',
                        'createdBy' => $user->id,
                        'modifiedBy' => $user->id,
                        'isDeleted' => 0
                    ]
                ];

                $expiredDocumentClaim = [
                    [
                        'id' => Str::uuid(36),
                        'actionId' => '5f24c3d8-94d8-4e57-adb3-bef3e000e7d0',
                        'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                        'claimType' => 'EXPIRED_DOCUMENTS_VIEW_DOCUMENT',
                    ],
                    [
                        'id' => Str::uuid(36),
                        'actionId' => '31ba8e74-8fa0-4c34-82ac-950e73a4c18e',
                        'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                        'claimType' => 'EXPIRED_DOCUMENTS_ACTIVATE_DOCUMENT',
                    ],
                    [
                        'id' => Str::uuid(36),
                        'actionId' => '086ce19f-5f1b-42ec-98ac-dea2d92901a3',
                        'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                        'claimType' => 'EXPIRED_DOCUMENTS_ARCHIVE_DOCUMENT',
                    ],
                ];

                Pages::insert($expiredPage);
                Actions::insert($expiredDocumentActions);
                RoleClaims::insert($expiredDocumentClaim);
            }
        });
    }
}
