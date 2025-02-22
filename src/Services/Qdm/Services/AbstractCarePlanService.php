<?php

/**
 * @package OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU GeneralPublic License 3
 */

namespace OpenEMR\Services\Qdm\Services;

use OpenEMR\Cqm\Qdm\BaseTypes\DateTime;

/**
 * Class AbstractCarePlanService
 * @package OpenEMR\Services\Qdm\Services
 *
 * This class gets data from the form_care_plan which contains plans for
 * interventions, medications, lab tests, etc
 */
abstract class AbstractCarePlanService extends AbstractQdmService
{
    /**
     * Care Plan Types that map the care plan type in the form_care_plan.care_plan_type field to their QDM models
     */
    const CARE_PLAN_TYPE_TEST_OR_ORDER = 'test_or_order'; // for LaboratoryTestOrderedService
    const CARE_PLAN_TYPE_PLAN_OF_CARE = 'plan_of_care'; // for DiagnosticStudyOrderedService
    const CARE_PLAN_TYPE_INTERVENTION = 'intervention'; // for InterventionOrderedService
    const CARE_PLAN_TYPE_PLANNED_MED_ACTIVITY = 'planned_medication_activity'; // for MedicationOrderService
    const CARE_PLAN_TYPE_MEDICATION = 'medication'; // for SubstanceRecommendedService
    const CARE_PLAN_TYPE_PROCEDURE_REC = 'procedure'; // for ProcedureRecommendedService

    abstract public function getCarePlanType();

    abstract public function getModelClass();

    public function getSqlStatement()
    {
        $carePlanType = $this->getCarePlanType();
        return "SELECT pid, `date`, code, codetext, description, care_plan_type, reason_code
            FROM form_care_plan
            WHERE care_plan_type = '" . add_escape_custom($carePlanType) . "'";
    }

    /**
     * @param array $record
     * @return mixed
     * @throws \Exception
     *
     * Since almost all the care plans contain the same data, we put the base code for making the model here.
     */
    public function makeQdmModel(array $record)
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass([
            'authorDatetime' => new DateTime([
                'date' => $record['date']
            ]),
        ]);

        // If there is a Negation reason noted why this plan was NOT done, add a negation. It will be in the 'code' column
        // with a code-system of "OID". Otherwise, add the code as usual
        if ($this->isNegationCode($record['code'])) {
            $model->negationRationale = $this->makeQdmCode($record['code']);
        } else {
            $model->addCode($this->makeQdmCode($record['code']));
        }

        // Add the reason code if we are supplied one
        if (!empty($record['reason_code'])) {
            $model->reason = $this->makeQdmCode($record['reason_code']);
        }

        return $model;
    }
}
