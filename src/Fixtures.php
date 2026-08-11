<?php

declare(strict_types=1);

final class Fixtures
{
    public static function insurance(): array
    {
        return [
            'medicare-oxygen'=>[
                'payer'=>'Medicare','title'=>'DME: Home Oxygen','category'=>'Durable Medical Equipment','status'=>'Current','effective'=>'Jan 1, 2026','reviewed'=>'Aug 2026','source'=>'CMS Medicare Coverage Database',
                'summary'=>'Coverage and documentation guide for home oxygen ordered at discharge or from the outpatient setting.',
                'requirements'=>['Confirm qualifying oxygen saturation or arterial blood gas criteria.','Document the condition supporting oxygen use and the qualifying test context.','Ensure the treating practitioner order contains the required equipment and flow details.','Send qualifying results and supporting documentation to the supplier before delivery.'],
                'misses'=>['Qualifying result is present but difficult for the supplier to locate.','Order does not match the equipment or flow rate described elsewhere.','Testing context is unclear or outside the required timing window.'],
                'links'=>[['Playbook','Arrange Home Oxygen','/playbooks/arrange-home-oxygen'],['Resource','Home Oxygen Qualification & Documentation','/resources/home-oxygen'],['Patient Resource','Home Oxygen: What to Expect','/patient-resources/home-oxygen']],
            ],
            'medicare-snf'=>['payer'=>'Medicare','title'=>'SNF Coverage Basics','category'=>'Post-acute Care','status'=>'Current','effective'=>'Jan 1, 2026','reviewed'=>'Jul 2026','source'=>'CMS Medicare Benefit Policy Manual','summary'=>'Practical eligibility and documentation reminders when planning skilled nursing facility placement.'],
            'medicaid-transport'=>['payer'=>'Maryland Medicaid','title'=>'Non-Emergency Medical Transportation','category'=>'Transportation','status'=>'Current','effective'=>'Jul 1, 2026','reviewed'=>'Jul 2026','source'=>'Maryland Medicaid','summary'=>'Operational reference for covered non-emergency transportation and common authorization considerations.'],
            'commercial-home-health'=>['payer'=>'Commercial Plans','title'=>'Home Health Prior Authorization Patterns','category'=>'Home Health','status'=>'Variable','effective'=>'Plan-specific','reviewed'=>'Aug 2026','source'=>'Payer portals and plan manuals','summary'=>'A comparison-oriented reference for common home health authorization requirements across commercial payers.'],
        ];
    }

    public static function playbooks(): array
    {
        return [
            'arrange-home-oxygen'=>[
                'title'=>'Arrange Home Oxygen','category'=>'DME','time'=>'10–20 min','steps'=>7,'summary'=>'Move from clinical need to a confirmed home delivery plan without losing the payer, documentation, supplier, or patient-education pieces.',
                'sequence'=>[
                    ['Confirm the need','Verify that home oxygen is clinically intended and identify the discharge timing.'],
                    ['Check payer requirements','Open the applicable oxygen coverage rule and confirm qualifying criteria.'],
                    ['Locate qualifying evidence','Make the qualifying test, timing, and clinical context easy to identify.'],
                    ['Complete the order','Confirm equipment, flow, route/use instructions, and prescriber requirements.'],
                    ['Send the supplier packet','Transmit the order, qualifying evidence, demographics/coverage information, and required notes using approved workflows.'],
                    ['Confirm acceptance and delivery','Do not treat “fax sent” as completion. Confirm supplier acceptance and the delivery plan.'],
                    ['Educate and close the loop','Provide patient education and document the operational handoff.'],
                ],
                'warning'=>'If the patient is leaving today, supplier acceptance and delivery confirmation are part of the workflow—not an optional follow-up.',
            ],
            'snf-placement'=>['title'=>'Skilled Nursing Facility Placement','category'=>'Post-acute','time'=>'15–30 min','steps'=>6,'summary'=>'Organize the referral, coverage, choice, acceptance, and handoff tasks needed for SNF placement.'],
            'home-health-referral'=>['title'=>'Home Health Referral','category'=>'Home Health','time'=>'10–15 min','steps'=>5,'summary'=>'Confirm eligibility, orders, agency acceptance, and a clean transition to home health.'],
            'transport-discharge'=>['title'=>'Arrange Discharge Transportation','category'=>'Transportation','time'=>'5–15 min','steps'=>5,'summary'=>'Choose the appropriate transportation pathway based on mobility, medical need, payer, and destination.'],
        ];
    }

    public static function knowledgeBase(): array
    {
        return [
            'discharge-documentation'=>['title'=>'Discharge Documentation Standard','topic'=>'Care Transitions','owner'=>'Clinical Operations','reviewed'=>'Aug 2, 2026','status'=>'Approved','summary'=>'Local expectations for documenting disposition, handoff status, unresolved barriers, and patient-facing education at transition of care.','sections'=>['Document the final disposition and the services arranged.','Record material barriers that remain unresolved at the time of transition.','Make handoff ownership clear when follow-up remains outstanding.','Document education provided and the patient/caregiver understanding when required.']],
            'dme-vendor-standard'=>['title'=>'DME Vendor Selection & Escalation','topic'=>'Durable Medical Equipment','owner'=>'Care Coordination','reviewed'=>'Jul 22, 2026','status'=>'Approved','summary'=>'Local process for selecting DME suppliers, escalating delivery barriers, and documenting unresolved vendor issues.'],
            'transport-sop'=>['title'=>'Discharge Transportation SOP','topic'=>'Transportation','owner'=>'Patient Flow','reviewed'=>'Jun 30, 2026','status'=>'Approved','summary'=>'Organization-specific workflow for routine, wheelchair, stretcher, and medically supported discharge transportation.'],
            'interpreter-standard'=>['title'=>'Language Access & Interpreter Standard','topic'=>'Patient Communication','owner'=>'Quality & Equity','reviewed'=>'Jul 12, 2026','status'=>'Approved','summary'=>'Local standard for qualified interpreter use and documentation during care coordination and discharge education.'],
        ];
    }

    public static function patientResources(): array
    {
        return [
            'home-oxygen'=>['title'=>'Home Oxygen: What to Expect','topic'=>'Respiratory / DME','pages'=>2,'reading'=>'Plain language','languages'=>'English · Spanish pending','summary'=>'Explains delivery, safe use, supplier contact, and what to do if equipment does not arrive as expected.','sections'=>['Before you leave: your care team and oxygen supplier confirm what equipment you need.','Delivery: oxygen may be delivered to your home or brought to the care setting depending on the plan.','Safety: keep oxygen away from flames, smoking, and heat sources.','Questions: call the oxygen supplier for equipment problems and your clinical team for health concerns.']],
            'home-health'=>['title'=>'Starting Home Health Services','topic'=>'Home Health','pages'=>2,'reading'=>'Plain language','languages'=>'English','summary'=>'What patients and caregivers can expect after a home health referral is accepted.'],
            'snf-transition'=>['title'=>'Going to a Skilled Nursing Facility','topic'=>'Post-acute','pages'=>3,'reading'=>'Plain language','languages'=>'English','summary'=>'Prepares patients and caregivers for the SNF transfer, arrival process, medications, and follow-up.'],
            'transport'=>['title'=>'Your Discharge Transportation Plan','topic'=>'Transportation','pages'=>1,'reading'=>'Plain language','languages'=>'English','summary'=>'Explains transportation timing, pickup expectations, and who to contact when plans change.'],
        ];
    }

    public static function search(string $query): array
    {
        $q=mb_strtolower(trim($query));
        $rows=[];
        foreach(self::insurance() as $slug=>$item)$rows[]=self::searchRow('Insurance',$item['title'],$item['summary'],'/insurance/'.$slug,$item['payer'].' · '.$item['reviewed']);
        foreach(self::playbooks() as $slug=>$item)$rows[]=self::searchRow('Playbook',$item['title'],$item['summary'],'/playbooks/'.$slug,$item['steps'].' steps · '.$item['time']);
        foreach(self::knowledgeBase() as $slug=>$item)$rows[]=self::searchRow('Knowledge Base',$item['title'],$item['summary'],'/knowledge-base/'.$slug,$item['owner'].' · '.$item['reviewed']);
        foreach(self::patientResources() as $slug=>$item)$rows[]=self::searchRow('Patient Resource',$item['title'],$item['summary'],'/patient-resources/'.$slug,$item['pages'].' pages · '.$item['reading']);
        $rows[]=self::searchRow('Resource','Home Oxygen Qualification & Documentation','Practical guide to qualifying testing, documentation, and discharge coordination.','/resources/home-oxygen','CMS Medicare Learning Network');
        if($q==='')return $rows;
        return array_values(array_filter($rows,static fn(array $row):bool=>str_contains(mb_strtolower(implode(' ',$row)),$q)));
    }

    private static function searchRow(string $type,string $title,string $summary,string $path,string $meta): array{return compact('type','title','summary','path','meta');}
}
