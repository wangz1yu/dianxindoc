<?php

use App\Models\UserNotificationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('retentionPeriod')->nullable();
            $table->integer('retentionAction')->nullable();
            $table->uuid('signById')->nullable();
            $table->dateTime('signDate')->nullable();
            $table->boolean('isExpired')->default(false);
            $table->dateTime('expiredDate')->nullable();

            $table->foreign('signById')->references('id')->on('users');
        });

        Schema::table('documentVersions', function (Blueprint $table) {
            $table->uuid('signById')->nullable();
            $table->dateTime('signDate')->nullable();

            $table->foreign('signById')->references('id')->on('users');
        });

        Schema::table('companyProfile', function (Blueprint $table) {
            $table->integer('archiveDocumentRetensionPeriod')->nullable();
            $table->boolean('allowPdfSignature')->default(true);
            $table->integer('emailLogRetentionPeriod')->nullable()->default(30);
            $table->integer('cronJobLogRetentionPeriod')->nullable()->default(30);
            $table->integer('loginAuditRetentionPeriod')->nullable()->default(30);
        });

        Schema::create('cronJobLogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jobName');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('output')->nullable();
            $table->integer('executionTime')->nullable();
            $table->timestamp('startedAt')->nullable();
            $table->timestamp('endedAt')->nullable();
        });

        Schema::create('emailLogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('senderEmail');
            $table->string('recipientEmail');
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['sent', 'failed'])->default('sent');;
            $table->text('errorMessage')->nullable();
            $table->timestamp('sentAt')->nullable();
        });

        Schema::create('emailLogAttachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('emailLogId')->nullable(false);
            $table->foreign('emailLogId')->references('id')->on('emailLogs');
            $table->string('path');
            $table->string('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('isSystemUser')->default(false);
        });

        Schema::create('documentSignatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('documentId')->nullable(false);
            $table->foreign('documentId')->references('id')->on('documents');

            $table->uuid('createdBy')->nullable(false);
            $table->foreign('createdBy')->references('id')->on('users');

            $table->string('signatureUrl');
            $table->dateTime('createdDate');
        });

        Schema::table('userNotifications', function (Blueprint $table) {
            $table->integer('notificationType')->default(UserNotificationTypeEnum::DOCUMENT_SHARE->value);

            $table->uuid('documentWorkflowId')->nullable(true);
            $table->foreign('documentWorkflowId')->references('id')->on('documentWorkflow');

            $table->uuid('fileRequestId')->nullable(true);
            $table->foreign('fileRequestId')->references('id')->on('fileRequests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('retentionPeriod');
            $table->dropColumn('retentionAction');
            $table->dropForeign(['signById']);
            $table->dropColumn('signById');
            $table->dropColumn('signDate');
            $table->dropColumn('isExpired');
            $table->dropColumn('expiredDate');
        });

        Schema::table('documentVersions', function (Blueprint $table) {
            $table->dropForeign(['signById']);
            $table->dropColumn('signById');
            $table->dropColumn('signDate');
        });

        Schema::table('companyProfile', function (Blueprint $table) {
            $table->dropColumn('archiveDocumentRetensionPeriod');
            $table->dropColumn('allowPdfSignature');
            $table->dropColumn('emailLogRetentionPeriod');
            $table->dropColumn('cronJobLogRetentionPeriod');
            $table->dropColumn('loginAuditRetentionPeriod');
        });

        Schema::dropIfExists('cronJobLogs');
        Schema::dropIfExists('emailLogs');
        Schema::dropIfExists('emailLogAttachments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('isSystemUser');
        });

        Schema::dropIfExists('documentSignatures');

        Schema::table('userNotifications', function (Blueprint $table) {
            $table->dropColumn('notificationType');
            $table->dropForeign(['documentWorkflowId']);
            $table->dropColumn('documentWorkflowId');
            $table->dropForeign(['fileRequestId']);
            $table->dropColumn('fileRequestId');
        });
    }
};
