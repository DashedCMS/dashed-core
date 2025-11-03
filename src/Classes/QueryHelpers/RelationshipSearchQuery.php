<?php

namespace Dashed\DashedCore\Classes\QueryHelpers;

class RelationshipSearchQuery
{
    public static function make($model, ?string $search, string $labelAttribute = 'name', string $keyAttribute = 'id', null|string|array $applyScopes = null): array
    {
        $query = $model::query();

        if ($search) {
            if (method_exists($model, 'scopeSearch')) {
                $query->search($search);
            } else {
                $query->where($labelAttribute, 'like', '%' . $search . '%');
            }
        }

        if ($applyScopes) {
            if (! is_array($applyScopes)) {
                $applyScopes = [$applyScopes];
            }

            foreach ($applyScopes as $scope) {
                if (is_string($scope) && method_exists($model, 'scope' . ucfirst($scope))) {
                    $query->{$scope}();
                }
            }
        }

        $results = $query->limit(50)->get();

        $options = [];

        foreach ($results as $result) {
            if ($labelAttribute === 'name' && method_exists($result, 'getNameWithParentsAttribute')) {
                $options[$result->$keyAttribute] = $result->nameWithParents;
            } else {
                $options[$result->$keyAttribute] = $result->$labelAttribute;
            }
        }

        return $options;
    }
}
