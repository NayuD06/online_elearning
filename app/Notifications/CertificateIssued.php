<?php

namespace App\Notifications;

use App\Models\Certificate;

/**
 * Congratulates a learner who finished a course and links to the certificate.
 */
class CertificateIssued extends InAppNotification
{
    public function __construct(public Certificate $certificate) {}

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'trophy',
            'title' => 'Chúc mừng! Bạn đã nhận chứng chỉ',
            'body' => "Hoàn thành khoá \"{$this->certificate->course_title}\". Bấm để xem và tải chứng chỉ.",
            'url' => route('certificates.show', $this->certificate),
        ];
    }
}
