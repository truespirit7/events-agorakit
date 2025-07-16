<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Group;
use App\User;
use App\Membership;


class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    protected function createGroupFromPlace(Request $request)
    {
        $group = new Group();

        // Основные поля  
        $group->name = $request->input('name');
        $group->body = $this->cleanHtmlDescription($request->input('description'));
        $group->group_type = Group::OPEN; // Для организаций по умолчанию открытые  

        // Обработка координат  
        if ($request->has('coordinates')) {
            $this->processCoordinates($group, $request->input('coordinates'));
        }

        // Обработка местоположения как в оригинальном контроллере  
        if ($request->has('location')) {
            $this->processLocation($group, $request);
        }

        // Сохранение внешних ссылок  
        $this->saveExternalLinks($group, $request);

        // Валидация и сохранение  
        if ($group->isInvalid()) {
            return response()->json(['errors' => $group->getErrors()], 422);
        }

        $group->save();

        // Создание администратора как в оригинальном контроллере  
        $this->createGroupAdmin($group);

        return response()->json(['message' => 'Organization created successfully', 'group' => $group], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
