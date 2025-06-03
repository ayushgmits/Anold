<?php


abstract class streamit_api_Routes
{

    protected $routes;

    public function __construct()
    {
        $this->routes();
    }

    protected function routes()
    {
        $this->routes = apply_filters(
            'streamit_api_admin_route_lists',
            array(
                'streamit_api_add_banner_tab' => [
                    'method' => 'get',
                    'action' => 'streamit_api_admin_pannel_controler@add_banner',
                    'module' => 'admin-pannel-controller',
                ],
                'streamit_api_add_slider_tab' => [
                    'method' => 'get',
                    'action' => 'streamit_api_admin_pannel_controler@add_slider',
                    'module' => 'admin-pannel-controller',
                ],
                'streamit_api_submit_dashbord_Data' => [
                    'method' => 'post',
                    'action' => 'streamit_api_admin_pannel_controler@submit_dashboard_data',
                    'module' => 'admin-pannel-controller',
                ]
            )
        );
    }

    public function get_route($route_name)
    {
        return $this->routes[$route_name];
    }

    public function has_route($route_name)
    {
        return array_key_exists($route_name, $this->routes);
    }
}
