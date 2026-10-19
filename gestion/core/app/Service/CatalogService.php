<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Catalog;
use DomainException;
use PDO;
use PDOException;
// Valida los catálogos y guarda cada cambio junto con su auditoría.
final class CatalogService
{
    public function __construct(private PDO $db) {}
    public static function definitions(): array
    {
        return require dirname(__DIR__, 3) . '/config/catalogs.php';
    }
    public static function definition(string $slug): array
    {
        return self::definitions()[$slug] ?? throw new DomainException('Catálogo desconocido.');
    }
    public function save(string $slug, array $input, ?int $id, int $userId): int
    {
        $definition = self::definition($slug);
        $model = new Catalog($this->db);
        $data = [];
        $old = $id ? $model->find($definition['table'], $id) : null;
        if ($id && !$old) {
            throw new DomainException('El registro no existe.');
        }
        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$key] = isset($input[$key]) ? 1 : 0;
                continue;
            }
            if (isset($input[$key]) && !is_scalar($input[$key])) {
                throw new DomainException('Valor inválido: ' . $field['label']);
            }
            $value = trim((string) ($input[$key] ?? ''));
            if ($value === '') {
                if ($field['required'] ?? false) {
                    throw new DomainException('Completa: ' . $field['label']);
                }
                $data[$key] = null;
                continue;
            }
            if (mb_strlen($value) > ($field['max'] ?? 190)) {
                throw new DomainException('Valor demasiado largo: ' . $field['label']);
            }
            if ($field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new DomainException('Correo inválido.');
            }
            if (in_array($field['type'], ['money', 'quantity'], true)) {
                $scale = $field['type'] === 'money' ? 2 : 3;
                if (!preg_match('/^\d{1,9}(\.\d{1,' . $scale . '})?$/D', $value)) {
                    throw new DomainException('Número inválido: ' . $field['label']);
                }
                if ($key === 'weight' && bccomp($value, '9999999.999', 3) > 0) {
                    throw new DomainException('Peso fuera de rango.');
                }
            }
            if (
                $field['type'] === 'duration' &&
                (!ctype_digit($value) || (int) $value < 5 || (int) $value > 480)
            ) {
                throw new DomainException('Duración: entre 5 y 480 minutos.');
            }
            if ($field['type'] === 'date') {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value || $value > date('Y-m-d')) {
                    throw new DomainException('Fecha de nacimiento inválida.');
                }
            }
            if ($field['type'] === 'select') {
                $reference = ctype_digit($value)
                    ? $model->find($field['table'], (int) $value)
                    : null;
                if (
                    !$reference ||
                    (!$reference['active'] && (string) ($old[$key] ?? '') !== $value)
                ) {
                    throw new DomainException('Selecciona un registro activo: ' . $field['label']);
                }
            }
            $data[$key] = $value;
        }
        $data['active'] = isset($input['active']) ? 1 : 0;
        $this->db->beginTransaction();
        try {
            $saved = $model->save($definition['table'], $data, $id);
            new AuditService($this->db)->record(
                $userId,
                $id ? 'update' : 'create',
                $definition['table'],
                $saved,
            );
            $this->db->commit();
            return $saved;
        } catch (PDOException $error) {
            $this->db->rollBack();
            if ($error->getCode() === '23000') {
                throw new DomainException(
                    'Ya existe ese código, nombre o código de barras, o la referencia es inválida.',
                );
            }
            throw $error;
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
