<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Collection;

class ModuleVersionFamily
{
    // PS-30: revisions share one title identity while retaining separate published history.
    public function ids(Module $module): Collection
    {
        $root = $module;
        $visited = [];

        while ($root->revision_of_id !== null && ! in_array($root->revision_of_id, $visited, true)) {
            $visited[] = $root->id;
            $root = Module::query()->findOrFail($root->revision_of_id);
        }

        $ids = collect([$root->id]);
        $frontier = [$root->id];

        while ($frontier !== []) {
            $children = Module::query()->whereIn('revision_of_id', $frontier)->pluck('id');
            $frontier = $children->diff($ids)->all();
            $ids = $ids->merge($frontier);
        }

        return $ids->unique()->values();
    }
}
