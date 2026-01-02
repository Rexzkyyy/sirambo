<?php
class DashboardController extends Controller {
    public function index() {
        $this->requireAuth();
        $this->view('dashboard/index', [
            'title' => 'Dashboard'
        ]);
    }
}
