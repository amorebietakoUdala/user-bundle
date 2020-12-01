<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Form\Factory;

/*
 *
 * @author ibilbao
 */
use Symfony\Component\Form\FormInterface;

interface FactoryInterface
{
    /**
     * @return FormInterface
     */
    public function createForm();
}
