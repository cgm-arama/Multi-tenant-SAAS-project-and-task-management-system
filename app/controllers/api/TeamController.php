<?php
class TeamController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        $userModel = $this->model('User');

        $sql = "SELECT * FROM users";
        $stmt = $userModel->query($sql);
        $users = $stmt->fetchAll();

        $data = [
            'users' => $users
        ];

        $this->view->render('team/index', $data);
    }
}