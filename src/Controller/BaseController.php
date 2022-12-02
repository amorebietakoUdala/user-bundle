<?php

namespace AMREU\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BaseController extends AbstractController
{
   protected array $queryParams = [];
   protected bool $ajax = false;

    protected function loadQueryParameters(Request $request) {
        $this->queryParams = $request->query->all();
        $this->ajax = $request->isXmlHttpRequest() || ( array_key_exists('ajax', $this->queryParams) && $this->queryParams['ajax'] === 'true' );        
    }

    protected function getQueryParams() : array {
        return $this->queryParams;
    }

    protected function getAjax(): bool {
        return $this->ajax;
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response { 
        $paginationParameters = $this->getQueryParams();
        $viewParameters = array_merge($parameters, $paginationParameters);
        return parent::render($view, $viewParameters, $response);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse {
        $paginationParameters = $this->getQueryParams();
        $viewParameters = array_merge($parameters, $paginationParameters);
        return parent::redirectToRoute($route, $viewParameters, $status);
    }    
}
