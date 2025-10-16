<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\RoleClaims;
use Illuminate\Support\Str;
use App\Models\Users;

class PermissionSeederV52 extends BaseSeeder
{
    public function run()
    {
        $this->runOnce(function () {
            $systemUser = Users::withoutGlobalScope('isSystemUser')->where('isSystemUser', true)->first();

            $actionsToAdd = [
                ['id' => 'bf3ec13f-1e81-40f3-ad7a-05523608e85c', 'createdBy' => $systemUser->id, 'modifiedBy' => $systemUser->id, 'name' => 'Manage Google Gemini API', 'order' => 4, 'pageId' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2', 'code' =>  'SETTINGS_MANAGE_GEMINI_API_KEY'],
                ['id' => '72ce114a-d299-4d7d-aeee-598167a4fabc', 'createdBy' => $systemUser->id, 'modifiedBy' => $systemUser->id, 'name' => 'Generate AI Powered Summary', 'order' => 5, 'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6', 'code' =>  'ALL_DOC_GENERATE_SUMMARY'],
                ['id' => '8b63ccd0-616a-4b97-8af6-aa49066a0a9e', 'createdBy' => $systemUser->id, 'modifiedBy' => $systemUser->id, 'name' => 'Generate AI Powered Summary', 'order' => 6, 'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd', 'code' =>  'ASSIGNED_DOC_GENERATE_SUMMARY'],
            ];

            $roleClaims = [
                ['id' => Str::uuid(36), 'actionId' => 'bf3ec13f-1e81-40f3-ad7a-05523608e85c', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'SETTINGS_MANAGE_GEMINI_API_KEY', 'claimValue' => ''],
                ['id' => Str::uuid(36), 'actionId' => '72ce114a-d299-4d7d-aeee-598167a4fabc', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'ALL_DOC_GENERATE_SUMMARY', 'claimValue' => ''],
                ['id' => Str::uuid(36), 'actionId' => '8b63ccd0-616a-4b97-8af6-aa49066a0a9e', 'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4', 'claimType' => 'ASSIGNED_DOC_GENERATE_SUMMARY', 'claimValue' => '']
            ];

            Actions::insert($actionsToAdd);
            RoleClaims::insert($roleClaims);
        });
    }
}
