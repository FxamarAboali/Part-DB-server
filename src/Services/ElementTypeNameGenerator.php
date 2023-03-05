<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2022 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Services;

use App\Entity\Attachments\Attachment;
use App\Entity\Attachments\AttachmentType;
use App\Entity\Contracts\NamedElementInterface;
use App\Entity\ProjectSystem\Project;
use App\Entity\LabelSystem\LabelProfile;
use App\Entity\Parameters\AbstractParameter;
use App\Entity\Parts\Category;
use App\Entity\Parts\Footprint;
use App\Entity\Parts\Manufacturer;
use App\Entity\Parts\MeasurementUnit;
use App\Entity\Parts\Part;
use App\Entity\Parts\PartLot;
use App\Entity\Parts\Storelocation;
use App\Entity\Parts\Supplier;
use App\Entity\PriceInformations\Currency;
use App\Entity\PriceInformations\Orderdetail;
use App\Entity\PriceInformations\Pricedetail;
use App\Entity\ProjectSystem\ProjectBOMEntry;
use App\Entity\UserSystem\Group;
use App\Entity\UserSystem\User;
use App\Exceptions\EntityNotSupportedException;
use function get_class;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElementTypeNameGenerator
{
    protected TranslatorInterface $translator;
    protected array $mapping;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        //Child classes has to become before parent classes
        $this->mapping = [
            Attachment::class => $this->translator->trans('attachment.label'),
            Category::class => $this->translator->trans('category.label'),
            AttachmentType::class => $this->translator->trans('attachment_type.label'),
            Project::class => $this->translator->trans('project.label'),
            ProjectBOMEntry::class => $this->translator->trans('project_bom_entry.label'),
            Footprint::class => $this->translator->trans('footprint.label'),
            Manufacturer::class => $this->translator->trans('manufacturer.label'),
            MeasurementUnit::class => $this->translator->trans('measurement_unit.label'),
            Part::class => $this->translator->trans('part.label'),
            PartLot::class => $this->translator->trans('part_lot.label'),
            Storelocation::class => $this->translator->trans('storelocation.label'),
            Supplier::class => $this->translator->trans('supplier.label'),
            Currency::class => $this->translator->trans('currency.label'),
            Orderdetail::class => $this->translator->trans('orderdetail.label'),
            Pricedetail::class => $this->translator->trans('pricedetail.label'),
            Group::class => $this->translator->trans('group.label'),
            User::class => $this->translator->trans('user.label'),
            AbstractParameter::class => $this->translator->trans('parameter.label'),
            LabelProfile::class => $this->translator->trans('label_profile.label'),
        ];
    }

    /**
     * Gets an localized label for the type of the entity.
     * A part element becomes "Part" ("Bauteil" in german) and a category object becomes "Category".
     * Useful when the type should be shown to user.
     * Throws an exception if the class is not supported.
     *
     * @param object|string $entity The element or class for which the label should be generated
     *
     * @return string the localized label for the entity type
     *
     * @throws EntityNotSupportedException when the passed entity is not supported
     */
    public function getLocalizedTypeLabel($entity): string
    {
        $class = is_string($entity) ? $entity : get_class($entity);

        //Check if we have an direct array entry for our entity class, then we can use it
        if (isset($this->mapping[$class])) {
            return $this->mapping[$class];
        }

        //Otherwise iterate over array and check for inheritance (needed when the proxy element from doctrine are passed)
        foreach ($this->mapping as $class => $translation) {
            if (is_a($entity, $class, true)) {
                return $translation;
            }
        }

        //When nothing was found throw an exception
        throw new EntityNotSupportedException(sprintf('No localized label for the element with type %s was found!', is_object($entity) ? get_class($entity) : (string) $entity));
    }

    /**
     * Returns a string like in the format ElementType: ElementName.
     * For example this could be something like: "Part: BC547".
     * It uses getLocalizedLabel to determine the type.
     *
     * @param NamedElementInterface $entity   the entity for which the string should be generated
     * @param bool                  $use_html If set to true, a html string is returned, where the type is set italic, and the name is escaped
     *
     * @return string The localized string
     *
     * @throws EntityNotSupportedException when the passed entity is not supported
     */
    public function getTypeNameCombination(NamedElementInterface $entity, bool $use_html = false): string
    {
        $type = $this->getLocalizedTypeLabel($entity);
        if ($use_html) {
            return '<i>'.$type.':</i> '.htmlspecialchars($entity->getName());
        }

        return $type.': '.$entity->getName();
    }
}
