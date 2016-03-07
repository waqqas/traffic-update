<?php

namespace common\workflows;

use raoul2000\workflow\source\file\IWorkflowDefinitionProvider;

class UserWorkflow implements IWorkflowDefinitionProvider{
    public function getDefinition()
    {
        return [
        'initialStatusId' => 'init',
            'status' => [
                'init' => [
                    'transition' => ['demand', 'daily'],
                ],
                'demand' => [
                    'transition' => ['daily'],
                ],
                'daily' => [
                  'transition' => ['demand'],
                ],
            ],
        ];
    }

}