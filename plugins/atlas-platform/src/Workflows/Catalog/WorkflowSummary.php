<?php
declare(strict_types=1);namespace Atlas\Platform\Workflows\Catalog;final class WorkflowSummary{public function __construct(public readonly string$id,public readonly string$title,public readonly string$summary,public readonly string$scope,public readonly int$version,public readonly int$stepCount){}}
