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
       if (
           $request->getMethod() === Request::METHOD_GET || 
           $request->getMethod() === Request::METHOD_POST || 
           $request->getMethod() === Request::METHOD_DELETE ) {
           $this->queryParams = $request->query->all();
       }
   }

   protected function getPaginationParameters() : array {
       $page = 1;
       $pageSize = 10;
       $sortName = 0;
       $sortOrder = 'asc';
       $returnUrl = null;

       if ( array_key_exists ('returnUrl', $this->queryParams) ) {
           $returnUrl = $this->queryParams['returnUrl'];
           $query = parse_url($this->queryParams['returnUrl'], PHP_URL_QUERY);
           parse_str($query,$this->queryParams);
       } 
       $page = $this->queryParams['page'] ?? $page;
       $pageSize = $this->queryParams['pageSize'] ?? $pageSize;
       $sortName = $this->queryParams['sortName'] ?? $sortName;
       $sortOrder = $this->queryParams['sortOrder'] ?? $sortOrder;
       return [
           'page' => $page,
           'pageSize' => $pageSize,
           'sortName' => $sortName,
           'sortOrder' => $sortOrder,
           'returnUrl' => $returnUrl,
       ];
   }

   protected function getAjax(): bool {
       if ( array_key_exists('ajax', $this->queryParams) ) {
           return $this->queryParams['ajax'] === 'true' ? true : false;
       }
       
       return false;
   }

   protected function render(string $view, array $parameters = [], Response $response = null): Response {
       $paginationParameters = $this->getPaginationParameters();
       $viewParameters = array_merge($parameters, $paginationParameters);
       return parent::render($view, $viewParameters, $response);
   }

   protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse {
       $paginationParameters = $this->getPaginationParameters();
       $viewParameters = array_merge($parameters, $paginationParameters);
       return parent::redirectToRoute($route, $viewParameters, $status);
   }    
}
