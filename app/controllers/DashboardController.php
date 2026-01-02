<?php
class DashboardController extends Controller {
    public function index() {
        $this->view('dashboard/index', [
            'title' => 'Dashboard'
        ]);
    }
}
