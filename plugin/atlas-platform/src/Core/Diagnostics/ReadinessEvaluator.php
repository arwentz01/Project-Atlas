<?php
declare(strict_types=1);namespace Atlas\Platform\Core\Diagnostics;final class ReadinessEvaluator{public function evaluate(array$checks):array{$failed=[];foreach($checks as$name=>$value){if($value!==true){$failed[]=(string)$name;}}return['status'=>$failed===[]?'ready':'not_ready','checks'=>$checks,'failed_checks'=>$failed,'timestamp'=>gmdate('c')];}}
