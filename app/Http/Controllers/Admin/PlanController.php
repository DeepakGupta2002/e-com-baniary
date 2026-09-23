<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function index()
    {
        $pageTitle = "Plans";
        $plans     = Plan::orderBy('price')->searchable(['name'])->get();
        return view('admin.plan.index', compact('pageTitle', 'plans'));
    }

    public function store(Request $request, $id = 0)
    {
        $request->validate([
            'name'            => 'required',
            'price'           => 'required|numeric|min:0',
            'bv'              => 'required|min:0|integer',
            'ref_com'         => 'required|numeric|min:0',
            'tree_com'        => 'required|numeric|min:0',
            'daily_capping'   => 'required|numeric|min:0',
            'monthly_capping' => 'required|numeric|min:0',
            'image'           => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!$id) {
            $notification = 'Plan added successfully';
            $plan         = new Plan();
        } else {
            $notification = 'Plan updated successfully';
            $plan         = Plan::findOrFail($id);
        }

        $plan->name            = $request->name;
        $plan->price           = $request->price;
        $plan->bv              = $request->bv;
        $plan->ref_com         = $request->ref_com;
        $plan->tree_com        = $request->tree_com;
        $plan->daily_capping   = $request->daily_capping;
        $plan->monthly_capping = $request->monthly_capping;

        if ($request->hasFile('image')) {
            try {
                $plan->image = fileUploader($request->image, getFilePath('plan'), getFileSize('plan'), $plan->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload plan image'];
                return back()->withNotify($notify);
            }
        }

        $plan->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Plan::changeStatus($id);
    }
}
