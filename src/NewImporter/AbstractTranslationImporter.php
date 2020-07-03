<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\NewImporter;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\LocalisedString;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The abstract importer handling translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @template TExport
 * @extends AbstractImporter<TExport>
 */
abstract class AbstractTranslationImporter extends AbstractImporter
{
    protected EntityManagerInterface $entityManager;
    protected IdCalculator $idCalculator;
    protected TranslationRepository $translationRepository;
    protected Validator $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        TranslationRepository $translationRepository,
        Validator $validator
    ) {
        $this->entityManager = $entityManager;
        $this->idCalculator = $idCalculator;
        $this->translationRepository = $translationRepository;
        $this->validator = $validator;
    }

    public function prepare(Combination $combination): void
    {
    }

    /**
     * @param Combination $combination
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     * @throws DBALException
     */
    public function import(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        $entities = $this->createTranslations($exportData, $offset, $limit);

        $this->translationRepository->persistTranslationsToCombination($combination->getId(), $entities);
    }

    /**
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     * @return array<Translation>|Translation[]
     */
    protected function createTranslations(ExportData $exportData, int $offset, int $limit): array
    {
        $results = [];
        foreach ($this->getChunkedExportEntities($exportData, $offset, $limit) as $entity) {
            $translations = $this->createTranslationsForEntity($exportData, $entity);
            foreach ($translations as $translation) {
                $this->validator->validateTranslation($translation);
                $translation->setId($this->idCalculator->calculateIdOfTranslation($translation));
                $results[] = $translation;
            }
        }
        return $results;
    }

    /**
     * Creates the translations for the specified entity.
     * @param ExportData $exportData
     * @param TExport $entity
     * @return array<Translation>|Translation[]
     */
    abstract protected function createTranslationsForEntity(ExportData $exportData, $entity): array;

    /**
     * Creates the translations from the localised strings.
     * @param string $type
     * @param string $name
     * @param LocalisedString $values
     * @param LocalisedString $descriptions
     * @return array<Translation>|Translation[]
     */
    protected function createTranslationsFromLocalisedStrings(
        string $type,
        string $name,
        LocalisedString $values,
        LocalisedString $descriptions
    ): array {
        $translations = [];
        foreach ($values->getTranslations() as $locale => $value) {
            if ($value !== '') {
                $translation = $this->createTranslationEntity($locale, $type, $name);
                $translation->setValue($value);
                $translations[$locale] = $translation;
            }
        }

        foreach ($descriptions->getTranslations() as $locale => $description) {
            if ($description !== '') {
                if (!isset($translations[$locale])) {
                    $translations[$locale] = $this->createTranslationEntity($locale, $type, $name);
                }
                $translations[$locale]->setDescription($description);
            }
        }

        foreach ($translations as $translation) {
            $this->validator->validateTranslation($translation);
            $translation->setId($this->idCalculator->calculateIdOfTranslation($translation));
        }
        return array_values($translations);
    }

    protected function createTranslationEntity(string $locale, string $type, string $name): Translation
    {
        $translation = new Translation();
        $translation->setLocale($locale)
                    ->setType($type)
                    ->setName($name);
        return $translation;
    }

    public function cleanup(): void
    {
    }

    /**
     * Searches for the specified item in the export data and returns it, if found.
     * @param ExportData $exportData
     * @param string $type
     * @param string $name
     * @return Item|null
     */
    protected function findItem(ExportData $exportData, string $type, string $name): ?Item
    {
        foreach ($exportData->getCombination()->getItems() as $item) {
            if ($item->getType() === $type && $item->getName() === $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Filters the translation which duplicate one of the items.
     * @param array<Translation>|Translation[] $translations
     * @param array<Item>|Item[] $items
     * @return array<Translation>|Translation[]
     */
    protected function filterDuplicatesToItems(array $translations, array $items): array
    {
        foreach ($translations as $key => $translation) {
            foreach ($items as $item) {
                $label = $item->getLabels()->getTranslations()[$translation->getLocale()] ?? '';
                $description = $item->getDescriptions()->getTranslations()[$translation->getLocale()] ?? '';

                if (
                    ($translation->getValue() === '' || $translation->getValue() === $label)
                    && ($translation->getDescription() === '' || $translation->getDescription() === $description)
                ) {
                    continue;
                }
                unset($translations[$key]);
            }
        }
        return $translations;
    }
}
