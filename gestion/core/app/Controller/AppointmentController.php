<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Appointment;
use App\Model\Catalog;
use App\Service\AppointmentService;
use Core\Controller;
use Core\Session;
use DateTimeImmutable;
use DomainException;

// Entrega la agenda HTML y sus eventos JSON usando las mismas reglas del backend.
final class AppointmentController extends Controller
{
    public function index(array $parameters = []): void
    {
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        $start = DateTimeImmutable::createFromFormat('!Y-m', $month);
        if (!$start || $start->format('Y-m') !== $month) {
            throw new DomainException('Mes inválido.');
        }
        $vet = (int) ($_GET['vet'] ?? 0);
        $catalog = new Catalog($this->context->db());
        $filter = (string) ($_GET['filter'] ?? 'all');
        if (!in_array($filter, ['all', 'pending', 'past'], true)) {
            throw new DomainException('Filtro inválido.');
        }
        $calendar = $parameters['calendar'] ?? false;
        $rows = (new Appointment($this->context->db()))->between(
            $start->format('Y-m-d'),
            $start->modify('+1 month')->format('Y-m-d'),
            $vet ?: null,
        );
        $rows = array_values(
            array_filter(
                $rows,
                static fn($row) => match ($filter) {
                    'pending' => in_array($row['status'], ['solicitada', 'confirmada'], true),
                    'past' => $row['end_at'] < date('Y-m-d H:i:s'),
                    default => true,
                },
            ),
        );
        $this->context->view->render(
            $calendar ? 'appointments/calendar.html.twig' : 'appointments/list.html.twig',
            [
                'month' => $month,
                // Conserva el día actual al alternar las vistas del mes vigente.
                'calendar_date' => $month === date('Y-m') ? date('Y-m-d') : $month . '-01',
                'vet' => $vet,
                'calendar' => $calendar,
                'filter' => $filter,
                'states' => AppointmentService::STATES,
                'request_key' => bin2hex(random_bytes(32)),
                'appointments' => $rows,
                'pets' => (new Appointment($this->context->db()))->pets(),
                'veterinarians' => $catalog->choices('veterinarians'),
                'services' => $catalog->choices('services'),
            ],
        );
    }
    public function calendar(array $parameters = []): void
    {
        $this->index(['calendar' => true]);
    }
    public function create(array $parameters = []): void
    {
        (new AppointmentService($this->context->db()))->create($_POST, (int) $_SESSION['user_id']);
        Session::flash('Cita registrada.');
        $this->redirect('/citas');
    }
    public function status(array $parameters): void
    {
        (new AppointmentService($this->context->db()))->changeStatus(
            (int) $parameters['id'],
            (string) ($_POST['status'] ?? ''),
            (string) ($_POST['notes'] ?? ''),
            (int) $_SESSION['user_id'],
        );
        Session::flash('Estado actualizado.');
        $this->redirect('/citas');
    }
    public function hours(array $parameters = []): void
    {
        (new AppointmentService($this->context->db()))->saveHours(
            $_POST,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Horario semanal guardado. Las citas existentes conservan su horario.');
        $this->redirect('/citas');
    }
    public function events(array $parameters = []): void
    {
        try {
            $start = new DateTimeImmutable((string) ($_GET['start'] ?? 'invalid'));
            $end = new DateTimeImmutable((string) ($_GET['end'] ?? 'invalid'));
        } catch (\Exception) {
            throw new DomainException('Intervalo inválido.');
        }
        if ($end <= $start || $end->getTimestamp() - $start->getTimestamp() > 93 * 86400) {
            throw new DomainException('Consulta como máximo 93 días.');
        }
        $rows = (new Appointment($this->context->db()))->between(
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            ((int) ($_GET['vet'] ?? 0)) ?: null,
        );
        $colors = [
            'solicitada' => '#b7791f',
            'confirmada' => '#176b63',
            'atendida' => '#2563eb',
            'cancelada' => '#64748b',
            'no_asistio' => '#b91c1c',
        ];
        $events = array_map(
            static fn($row) => [
                'id' => $row['id'],
                'url' => '/citas/' . $row['id'],
                'title' =>
                    $row['pet_name'] .
                    ' · ' .
                    $row['service_name'] .
                    ' · ' .
                    $row['veterinarian_name'],
                'start' => str_replace(' ', 'T', $row['start_at']),
                'end' => str_replace(' ', 'T', $row['end_at']),
                'color' => $colors[$row['status']],
            ],
            $rows,
        );
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($events, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
