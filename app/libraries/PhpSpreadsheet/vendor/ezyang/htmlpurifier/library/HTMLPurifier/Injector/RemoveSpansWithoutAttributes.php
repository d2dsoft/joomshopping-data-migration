<?php

/**
 * D2dSoft
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL v3.0) that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL: https://d2d-soft.com/license/AFL.txt
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this extension/plugin/module to newer version in the future.
 *
 * @author     D2dSoft Developers <developer@d2d-soft.com>
 * @copyright  Copyright (c) 2021 D2dSoft (https://d2d-soft.com)
 * @license    https://d2d-soft.com/license/AFL.txt
 */

/**
 * Injector that removes spans with no attributes
 */
class HTMLPurifier_Injector_RemoveSpansWithoutAttributes extends HTMLPurifier_Injector
{
    /**
     * @type string
     */
    public $name = 'RemoveSpansWithoutAttributes';

    /**
     * @type array
     */
    public $needed = array('span');

    /**
     * @type HTMLPurifier_AttrValidator
     */
    private $attrValidator;

    /**
     * Used by AttrValidator.
     * @type HTMLPurifier_Config
     */
    private $config;

    /**
     * @type HTMLPurifier_Context
     */
    private $context;

    /**
     * @type SplObjectStorage
     */
    private $markForDeletion;

    public function __construct()
    {
        $this->markForDeletion = new SplObjectStorage();
    }

    public function prepare($config, $context)
    {
        $this->attrValidator = new HTMLPurifier_AttrValidator();
        $this->config = $config;
        $this->context = $context;
        return parent::prepare($config, $context);
    }

    /**
     * @param HTMLPurifier_Token $token
     */
    public function handleElement(&$token)
    {
        if ($token->name !== 'span' || !$token instanceof HTMLPurifier_Token_Start) {
            return;
        }

        // We need to validate the attributes now since this doesn't normally
        // happen until after MakeWellFormed. If all the attributes are removed
        // the span needs to be removed too.
        $this->attrValidator->validateToken($token, $this->config, $this->context);
        $token->armor['ValidateAttributes'] = true;

        if (!empty($token->attr)) {
            return;
        }

        $nesting = 0;
        while ($this->forwardUntilEndToken($i, $current, $nesting)) {
        }

        if ($current instanceof HTMLPurifier_Token_End && $current->name === 'span') {
            // Mark closing span tag for deletion
            $this->markForDeletion->attach($current);
            // Delete open span tag
            $token = false;
        }
    }

    /**
     * @param HTMLPurifier_Token $token
     */
    public function handleEnd(&$token)
    {
        if ($this->markForDeletion->contains($token)) {
            $this->markForDeletion->detach($token);
            $token = false;
        }
    }
}

// vim: et sw=4 sts=4