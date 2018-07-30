<?php

class ControllerUserLogin extends Controller
{

    /**
     * @important dont use this method/route from inside a controller
     * use this method/route as a preaction so that login is mandatory for all pages
     * returns the current browser url route as action object
     */
    function index()
    {
        $target = (empty($this->request->get['route']) || ($this->request->get['route'] == 'user/login')) ? 'common/home' : $this->request->get['route'];

        if (!empty($this->request->post['username']) && !empty($this->request->post['password'])) {
            $username = $this->request->post['username'];
            $password = $this->request->post['password'];

            if ($this->portal_user->login2($username, $password)) { //success
                return new Action($target);
            }
        }
        //login required
        $data = $this->load->_controller('common/layout')->context();
        $data['msg'] = ($prev_msg = $this->session->flash()) ? $prev_msg : 'Please login';
        $data['login_form_target'] = '';
        $this->response->setOutput($this->load->twig('/user/login.twig', $data));
        $this->response->setStatusCode(302, 'login required');
    }

    /*
     * check login status in a page
     */

    function check_logged()
    {
        $referrer = $this->url->link(str_replace('route=', '', $_SERVER['QUERY_STRING'])); // current url
        if (!$this->portal_user->isLogged()) {
            $this->response->redirect($this->url->link('user/login', 'ref=' . $referrer, 'SSL'));
            return true;
        }
    }

    function isLogged()
    {
        $logged_status = $this->portal_user->isLogged();
        $output = array(
            'success' => '',
            'note' => $logged_status ? 'logged on!' : 'not logged on!',
            'data' => array(
                'logged_status' => $logged_status
            )
        );
        $this->response->setOutput(json_encode($output));
    }

    /**
     * check login status as preaction
     * @return \Action
     * TODO: implement referrer
     */
    function preaction_check_logged()
    {
        if (!$this->portal_user->isLogged()) {
            return new Action('user/login');
        }
    }

    function logout()
    {
        $this->portal_user->logout();
        $this->response->redirect($this->url->link('user/login'));
    }

}
