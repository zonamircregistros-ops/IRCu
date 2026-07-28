<?php
declare(strict_types=1);

/**
 * @return array<int, array{label: string, href: string, count: int}>
 */
function get_admin_notifications(): array
{
    $items = [];

    $pendingBnc = (int) db()->query("SELECT COUNT(*) FROM bnc_requests WHERE status = 'pendiente'")->fetchColumn();
    if ($pendingBnc > 0) {
        $items[] = ['label' => 'Solicitudes BNC pendientes', 'href' => 'bnc_requests.php', 'count' => $pendingBnc];
    }

    $pendingAppeals = (int) db()->query("SELECT COUNT(*) FROM gline_appeals WHERE status = 'pendiente'")->fetchColumn();
    if ($pendingAppeals > 0) {
        $items[] = ['label' => 'Apelaciones G-Line pendientes', 'href' => 'gline_appeals.php', 'count' => $pendingAppeals];
    }

    $pendingIrcop = (int) db()->query("SELECT COUNT(*) FROM ircop_applications WHERE status = 'pendiente'")->fetchColumn();
    if ($pendingIrcop > 0) {
        $items[] = ['label' => 'Postulaciones IRCop pendientes', 'href' => 'ircop_applications.php', 'count' => $pendingIrcop];
    }

    $openTickets = (int) db()->query("SELECT COUNT(*) FROM tickets WHERE status = 'abierto'")->fetchColumn();
    if ($openTickets > 0) {
        $items[] = ['label' => 'Tickets abiertos', 'href' => 'tickets.php', 'count' => $openTickets];
    }

    return $items;
}
