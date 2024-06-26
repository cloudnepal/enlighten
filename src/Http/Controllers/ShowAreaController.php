<?php

namespace Styde\Enlighten\Http\Controllers;

use Illuminate\Support\Collection;
use Styde\Enlighten\Facades\Settings;
use Styde\Enlighten\Models\Area;
use Styde\Enlighten\Models\Endpoint;
use Styde\Enlighten\Models\ExampleRequest;
use Styde\Enlighten\Models\Module;
use Styde\Enlighten\Models\ModuleCollection;
use Styde\Enlighten\Models\Run;
use Styde\Enlighten\Section;

class ShowAreaController
{
    public function __invoke(Run $run, string $areaSlug = null)
    {
        $area = $this->getArea($run, $areaSlug);

        $action = $area->view;

        if (! in_array($action, ['features', 'modules', 'endpoints'])) {
            $action = 'features';
        }

        return $this->$action($run, $area);
    }

    private function modules(Run $run, Area $area)
    {
        return view('enlighten::area.modules', [
            'area' => $area,
            'modules' => $this->wrapByModule($this->getGroups($run, $area)->load('stats')),
        ]);
    }

    private function features(Run $run, Area $area)
    {
        $groups = $this->getGroups($run, $area)
            ->load([
                'examples' => function ($q) {
                    $q->withCount('queries');
                },
                'examples.group',
                'examples.requests',
                'examples.exception'
            ]);

        return view('enlighten::area.features', [
            'area' => $area,
            'showQueries' => Settings::show(Section::QUERIES),
            'groups' => $groups,
        ]);
    }

    private function endpoints(Run $run, Area $area)
    {
        $requests = ExampleRequest::query()
            ->select('id', 'example_id', 'request_method', 'request_path')
            ->addSelect('route', 'response_status', 'response_headers')
            ->with([
                'example:id,group_id,title,slug,status,order_num',
                'example.group:id,slug,run_id',
            ])
            ->when($area->isNotDefault(), function ($q) use ($area) {
                $q->whereHas('example.group', function ($q) use ($area) {
                    $q->where('area', $area->slug);
                });
            })
            ->whereHas('example.group.run', function ($q) use ($run) {
                $q->where('id', $run->id);
            })
            ->where('follows_redirect', false)
            ->get();

        $endpoints = $requests
            ->groupBy('signature')
            ->map(fn ($requests) => new Endpoint(
                $requests->first()->request_method,
                $requests->first()->route_or_path,
                $requests->unique(fn ($response) => $response->signature.$response->example->slug)->sortBy('example.order')
            ))
            ->sortBy('method_index');

        return view('enlighten::area.endpoints', [
            'area' => $area,
            'modules' => $this->wrapByModule($endpoints),
        ]);
    }

    private function getArea(Run $run, string $areaSlug = null): Area
    {
        if (empty($areaSlug)) {
            return $this->defaultArea();
        }

        return $run->areas->firstWhere('slug', $areaSlug) ?: $this->defaultArea();
    }

    private function defaultArea(): Area
    {
        return new Area('', trans('enlighten::messages.all_areas'), config('enlighten.area_view', 'features'));
    }

    private function getGroups(Run $run, Area $area): Collection
    {
        // We always want to get the collection with all the groups
        // because we use them to build the menu. So by filtering
        // at a collection level we're actually saving a query.
        return $run->groups
            ->when($area->isNotDefault(), fn ($collection) => $collection->where('area', $area->slug))
            ->sortBy('order');
    }

    private function wrapByModule(Collection $groups): ModuleCollection
    {
        return Module::all()->wrapGroups($groups)->whereHasGroups();
    }
}
