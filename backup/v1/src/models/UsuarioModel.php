<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre, s.nombre AS sitio_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.rol_id
             LEFT JOIN sitios s ON s.id = u.sitio_id
             WHERE u.id = ? AND u.activo = 1 LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function list(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nombre, u.email, u.activo, u.ultimo_login, u.created_at,
                    r.nombre AS rol_nombre, s.nombre AS sitio_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.rol_id
             LEFT JOIN sitios s ON s.id = u.sitio_id
             ORDER BY u.id DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password, rol_id, sitio_id, linea_negocio_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['rol_id'],
            $data['sitio_id']          ?? null,
            $data['linea_negocio_id']  ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['nombre', 'email', 'rol_id', 'sitio_id', 'activo'] as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (!$fields) return false;
        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$id]);
    }
}
