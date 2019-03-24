<?php

namespace Lankerd\GroundworkBundle\Model;

interface ConditionsInterface{
    /**
     * This will allow for a bridge between an
     * outside handler and the @CoreModel. This
     * will allow for the CoreModel to take in
     * any possible custom conditions that
     * may be required during import.
     *
     * @return mixed
     */
    public function conditions();
}