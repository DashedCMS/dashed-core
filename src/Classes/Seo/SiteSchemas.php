<?php

namespace Dashed\DashedCore\Classes\Seo;

use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\BaseType;

/**
 * Zet het profiel-array van BusinessProfile om in schema-objecten. Geen
 * database en geen request: alleen het array erin, het schema eruit, zodat
 * dit met een handgemaakt profiel te testen is. Een ontbrekende waarde
 * laat de eigenschap weg; Google rekent een lege string als fout.
 */
class SiteSchemas
{
    public static function organization(array $profile): BaseType
    {
        $type = in_array($profile['type'] ?? null, BusinessProfile::TYPES, true) ? $profile['type'] : 'Organization';

        /** @var BaseType $schema */
        $schema = Schema::{lcfirst($type)}();
        $schema->setProperty('@id', $profile['id']);
        $schema->name($profile['name']);
        $schema->legalName($profile['name']);
        $schema->url($profile['url']);

        if ($logo = $profile['logo'] ?? null) {
            $image = Schema::imageObject()->url($logo['url']);

            if (! empty($logo['width']) && ! empty($logo['height'])) {
                $image->width((int) $logo['width'])->height((int) $logo['height']);
            }

            $schema->logo($image)->image($image);
        }

        if ($profile['telephone'] ?? null) {
            $schema->telephone($profile['telephone']);
        }

        if ($profile['email'] ?? null) {
            $schema->email($profile['email']);
        }

        if ($address = $profile['address'] ?? null) {
            $postal = Schema::postalAddress();

            foreach (['streetAddress', 'postalCode', 'addressLocality', 'addressCountry'] as $property) {
                if (! empty($address[$property])) {
                    $postal->setProperty($property, $address[$property]);
                }
            }

            $schema->address($postal);
        }

        // Een OnlineStore of Organization is geen plaats: openingstijden,
        // prijsklasse en geo bestaan daar niet en de validator meldt ze.
        // Uren horen daar bij de klantenservice (contactPoint.hoursAvailable).
        $isLocal = in_array($type, BusinessProfile::LOCAL_TYPES, true);
        $hours = ! empty($profile['openingHours']) ? array_map(
            fn (array $rule) => Schema::openingHoursSpecification()
                ->dayOfWeek($rule['days'])
                ->opens($rule['opens'])
                ->closes($rule['closes']),
            array_values($profile['openingHours']),
        ) : [];

        if (($profile['telephone'] ?? null) || ($profile['email'] ?? null) || (! $isLocal && $hours)) {
            $contact = Schema::contactPoint()->contactType('customer service');

            if ($profile['telephone'] ?? null) {
                $contact->telephone($profile['telephone']);
            }

            if ($profile['email'] ?? null) {
                $contact->email($profile['email']);
            }

            if (! $isLocal && $hours) {
                $contact->hoursAvailable($hours);
            }

            $schema->contactPoint($contact);
        }

        if (! empty($profile['sameAs'])) {
            $schema->sameAs(array_values($profile['sameAs']));
        }

        if ($isLocal && $hours) {
            $schema->openingHoursSpecification($hours);
        }

        if ($isLocal && ($profile['priceRange'] ?? null)) {
            $schema->priceRange($profile['priceRange']);
        }

        if ($isLocal && ($geo = $profile['geo'] ?? null)) {
            $schema->geo(Schema::geoCoordinates()->latitude($geo['latitude'])->longitude($geo['longitude']));
        }

        if ($rating = $profile['rating'] ?? null) {
            $schema->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($rating['value'])
                    ->bestRating(5)
                    ->worstRating(1)
                    ->reviewCount($rating['count']),
            );
        }

        if (! empty($profile['reviews'])) {
            $schema->review(array_map(
                fn (array $review) => Schema::review()
                    ->name(mb_strlen($review['body']) > 60 ? mb_substr($review['body'], 0, 57) . '...' : ($review['body'] ?: 'Review'))
                    ->author(Schema::person()->name($review['name'] ?: 'Anoniem'))
                    ->reviewBody($review['body'])
                    ->reviewRating(Schema::rating()->ratingValue((int) $review['stars'])->bestRating(5)->worstRating(1)),
                array_values($profile['reviews']),
            ));
        }

        return $schema;
    }

    public static function webSite(array $profile): BaseType
    {
        $schema = Schema::webSite();
        $schema->setProperty('@id', rtrim($profile['url'], '/') . '/#website');
        $schema->name($profile['name']);
        $schema->url($profile['url']);
        $schema->inLanguage($profile['locale']);
        $schema->publisher(['@id' => $profile['id']]);

        if ($profile['searchUrl'] ?? null) {
            $schema->potentialAction(
                Schema::searchAction()
                    ->target($profile['searchUrl'])
                    ->setProperty('query-input', 'required name=search_term_string'),
            );
        }

        return $schema;
    }
}
