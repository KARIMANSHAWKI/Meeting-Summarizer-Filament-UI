<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('organization.{organizationId}', function ($user, $organizationId) {
    // Allow super admins or users in the same organization
    if (($user->is_super_admin )) {
        return ['id' => $user->id, 'organization_id' => null];
    }
    return (string) $user->organization_id === (string) $organizationId
        ? ['id' => $user->id, 'organization_id' => $user->organization_id]
        : false;
});
