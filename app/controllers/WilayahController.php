<?php
require_once __DIR__ . '/../models/WilayahModel.php';

class WilayahController extends Controller {
    private WilayahModel $m;

    public function __construct() {
        $this->m = new WilayahModel();
    }

    public function index() {
        $this->view('master/wilayah_index', [
            'title' => 'Master Wilayah',
            'rows'  => $this->m->all(),
        ]);
    }

    public function store() {
        $d = [
            'kode_wilayah'  => trim($_POST['kode_wilayah'] ?? ''),
            'nama_wilayah'  => trim($_POST['nama_wilayah'] ?? ''),
            'kode_induk'    => trim($_POST['kode_induk'] ?? ''),
            'level_wilayah' => $_POST['level_wilayah'] ?? 'PROVINSI',
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($d['kode_wilayah'] === '' || $d['nama_wilayah'] === '') {
            die("Kode & Nama wajib diisi");
        }

        $this->m->store($d);
        $this->redirect('/wilayah');
    }

    public function update() {
        $d = [
            'kode_wilayah'  => trim($_POST['kode_wilayah'] ?? ''),
            'nama_wilayah'  => trim($_POST['nama_wilayah'] ?? ''),
            'kode_induk'    => trim($_POST['kode_induk'] ?? ''),
            'level_wilayah' => $_POST['level_wilayah'] ?? 'PROVINSI',
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($d['kode_wilayah'] === '' || $d['nama_wilayah'] === '') {
            die("Kode & Nama wajib diisi");
        }

        $this->m->update($d);
        $this->redirect('/wilayah');
    }

    public function delete() {
        $kode = trim($_POST['kode_wilayah'] ?? '');
        if ($kode === '') die("kode_wilayah kosong");
        $this->m->delete($kode);
        $this->redirect('/wilayah');
    }
}
