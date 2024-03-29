<?php

namespace AMREU\UserBundle\Form\Factory;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactory;

/**
 * Description of FormFactory.
 *
 * @author ibilbao
 */
class UserFormFactory implements FactoryInterface
{
    private $class;

    private $formType;

    private $formFactory;

    public function __construct(string $class, string $formType, FormFactory $formFactory)
    {
        $this->class = $class;
        $this->formType = $formType;
        $this->formFactory = $formFactory;
    }

    public function createForm(array $options = []): FormInterface
    {
        $options['data_class'] = $this->class;

        return $this->formFactory->createNamed($this->getClass(), $this->formType, null, $options);
    }

    /**
     * Returns the classname without namespace.
     *
     * @return string
     */
    private function getClass(): string
    {
        $end = explode('\\', strtolower($this->class));

        return array_pop($end);
    }
}
