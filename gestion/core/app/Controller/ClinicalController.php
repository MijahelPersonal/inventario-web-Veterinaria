<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Catalog;
use App\Model\ClinicalRecord;
use App\Service\AppointmentService;
use App\Service\ClinicalService;
use App\Service\DocumentService;
use Core\Controller;
use Core\Session;
use DomainException;

// Muestra fichas con historial y limita las notas de atención al rol autorizado.
final class ClinicalController extends Controller
{
    public function profile(array $parameters): void
    {
        $kind = $parameters['kind'];
        $catalog = new Catalog($this->context->db());
        $record = $catalog->find(
            $kind === 'mascotas' ? 'pets' : 'veterinarians',
            (int) $parameters['id'],
        );
        if (!$record) {
            throw new DomainException('El registro no existe.');
        }
        $this->context->view->render('clinical/profile.html.twig', [
            'kind' => $kind,
            'record' => $record,
            'client' =>
                $kind === 'mascotas' ? $catalog->find('clients', (int) $record['client_id']) : null,
            'appointments' => (new ClinicalRecord($this->context->db()))->appointments(
                $kind,
                (int) $record['id'],
            ),
        ]);
    }
    public function appointment(array $parameters): void
    {
        $record = (new ClinicalRecord($this->context->db()))->appointment((int) $parameters['id']);
        if (!$record) {
            throw new DomainException('La cita no existe.');
        }
        $this->context->view->render('clinical/appointment.html.twig', [
            'record' => $record,
            'veterinarians' => (new Catalog($this->context->db()))->choices('veterinarians'),
            'states' => AppointmentService::STATES,
        ]);
    }
    public function notes(array $parameters): void
    {
        (new ClinicalService($this->context->db()))->notes(
            (int) $parameters['id'],
            $_POST,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Notas guardadas.');
        $this->redirect('/citas/' . $parameters['id']);
    }
    public function reschedule(array $parameters): void
    {
        (new ClinicalService($this->context->db()))->reschedule(
            (int) $parameters['id'],
            $_POST,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Cita reprogramada.');
        $this->redirect('/citas/' . $parameters['id']);
    }
    public function pdf(array $parameters): void
    {
        $record = (new ClinicalRecord($this->context->db()))->appointment((int) $parameters['id']);
        if (!$record) {
            throw new DomainException('La cita no existe.');
        }
        (new DocumentService())->pdf(
            $this->context->view->html('documents/appointment.html.twig', ['record' => $record]),
            'atencion-' . $record['id'] . '.pdf',
        );
    }
}
