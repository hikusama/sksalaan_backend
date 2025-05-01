<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreyouthInfoRequest;
use App\Http\Requests\UpdateyouthInfoRequest;
use App\Models\YouthInfo;

class YouthInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return YouthInfo::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreyouthInfoRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(YouthInfo $youthInfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(YouthInfo $youthInfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateyouthInfoRequest $request, YouthInfo $youthInfo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(YouthInfo $youthInfo)
    {
        //
    }


}
