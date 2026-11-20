<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case REQUESTED = 'REQUESTED';
    case BOOKED = 'BOOKED';
    case UPLOADED = 'UPLOADED';
    case ARCHIVED = 'ARCHIVED';
    case CREATED = 'CREATED';
    case EDIT = 'EDIT';
    case SIGNED = 'SIGNED';
    case STAMP = 'STAMP';
    case REJECT_BY_DC = 'REJECT_BY_DC';
    case PASS_BY_DC = 'PASS_BY_DC';
    case REJECT_BY_CHECKER = 'REJECT_BY_CHECKER';
    case CHECKED = 'CHECKED';
    case REJECT_BY_APPROVER = 'REJECT_BY_APPROVER';
    case APPROVED = 'APPROVED';
    case MARK_AS_READ = 'MARK_AS_READ';
    case REVISE = 'REVISE';
    case OBSOLETE = 'OBSOLETE';
    case DOWNLOAD_INTERNAL = 'DOWNLOAD_INTERNAL';
    case DOWNLOAD_EKSTERNAL = 'DOWNLOAD_EKSTERNAL';

    public function label(): string
    {
        return match($this) {
            self::REQUESTED => 'REQUESTED',
            self::BOOKED => 'BOOKED',
            self::UPLOADED => 'UPLOADED',
            self::SIGNED => 'SIGNED',
            self::ARCHIVED => 'ARCHIVED',
            self::CREATED => 'Created',
            self::EDIT => 'Edit',
            self::STAMP => 'Stamp',
            self::REJECT_BY_DC => 'Rejected by DC',
            self::PASS_BY_DC => 'Passed by DC',
            self::REJECT_BY_CHECKER => 'Rejected by Checker',
            self::CHECKED => 'Checked',
            self::REJECT_BY_APPROVER => 'Rejected by Approver',
            self::APPROVED => 'Approved',
            self::MARK_AS_READ => 'Mark as read',
            self::REVISE => 'Revise',
            self::OBSOLETE => 'Obsolete',
        };
    }
}
