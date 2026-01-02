<?php
require_once __DIR__ . '/../config/database.php';

class WilayahModel {
    public function all(): array {
        $stmt = db()->query("SELECT * FROM mst_wilayah ORDER BY level_wilayah, nama_wilayah");
        return $stmt->fetchAll();
    }

    public function store(array $d): void {
        $sql = "INSERT INTO mst_wilayah (kode_wilayah, nama_wilayah, kode_induk, level_wilayah, is_active)
                VALUES (:kode, :nama, :induk, :level, :aktif)";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':kode'  => $d['kode_wilayah'],
            ':nama'  => $d['nama_wilayah'],
            ':induk' => $d['kode_induk'] ?: null,
            ':level' => $d['level_wilayah'],
            ':aktif' => (int)$d['is_active'],
        ]);
    }

    public function update(array $d): void {
        $sql = "UPDATE mst_wilayah
                SET nama_wilayah=:nama, kode_induk=:induk, level_wilayah=:level, is_active=:aktif
                WHERE kode_wilayah=:kode";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':kode'  => $d['kode_wilayah'],
            ':nama'  => $d['nama_wilayah'],
            ':induk' => $d['kode_induk'] ?: null,
            ':level' => $d['level_wilayah'],
            ':aktif' => (int)$d['is_active'],
        ]);
    }

    public function delete(string $kode): void {
        $stmt = db()->prepare("DELETE FROM mst_wilayah WHERE kode_wilayah=?");
        $stmt->execute([$kode]);
    }
}
