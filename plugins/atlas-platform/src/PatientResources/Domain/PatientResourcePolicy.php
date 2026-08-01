<?php
declare(strict_types=1);
namespace Atlas\Platform\PatientResources\Domain;
use InvalidArgumentException;
final class PatientResourcePolicy{private const FIELDS=['clinic_phone'=>80,'clinic_address'=>300,'approved_footer'=>500];public function normalize(array $input):array{$safe=[];foreach(self::FIELDS as $field=>$limit){if(!isset($input[$field])){continue;}$value=trim((string)$input[$field]);if(strlen($value)>$limit){throw new InvalidArgumentException("{$field} exceeds its limit.");}$safe[$field]=$value;}return $safe;}public function validColor(string $color):bool{return preg_match('/^#[0-9a-fA-F]{6}$/',$color)===1;}}
