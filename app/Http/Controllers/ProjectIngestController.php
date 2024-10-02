<?php

namespace App\Http\Controllers;

use App\Helpers\IngestRuleFactory;
use App\Models\IngestRule;
use App\Models\Project;
use GuzzleHttp\Utils;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectIngestController extends Controller
{

    public function index(Project $project)
    {
        $returnData = [];
        $ruleModel = IngestRule::select('*')
            ->where('project_id', '=', $project->id)
            ->get()->first();

        $rules = Utils::jsonDecode($ruleModel->rules);

        $returnData['project'] = $project;
        $returnData['rules'] = $rules;
        return Inertia::render('Project/IngestRules', $returnData);
    }

    public function store(Project $project, Request $request)
    {
        $rule = IngestRule::select('*')
            ->where('project_id', '=', $project->id)
            ->get()->first();

        try {
            IngestRuleFactory::create($request->rules);
        } catch (\App\Exceptions\InvalidIngestOperationException $th) {
            return redirect()->back()->withErrors(['ingestOperation' => $th->getMessage()]);
        } catch(\App\Exceptions\IngestRuleCriteriaException $th){
            return redirect()->back()->withErrors(['ingestCriteria' => $th->getMessage()]);
        }

        $rule->rules = Utils::jsonEncode($request->rules);
        $rule->save();

        return redirect()->back();
    }
}
