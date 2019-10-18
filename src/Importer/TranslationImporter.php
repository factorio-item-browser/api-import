<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of the translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporter implements ImporterInterface
{
    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;

    /**
     * The translation repository.
     * @var TranslationRepository
     */
    protected $translationRepository;

    /**
     * The parsed translations.
     * @var array|Translation[]
     */
    protected $translations = [];

    /**
     * Initializes the importer.
     * @param IdCalculator $idCalculator
     * @param TranslationRepository $translationRepository
     */
    public function __construct(IdCalculator $idCalculator, TranslationRepository $translationRepository)
    {
        $this->idCalculator = $idCalculator;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $translationAggregator = new TranslationAggregator();

        $this->processMods($translationAggregator, $exportData->getCombination()->getMods());
        $this->processItems($translationAggregator, $exportData->getCombination()->getItems());
        $this->processMachines($translationAggregator, $exportData->getCombination()->getMachines());
        $this->processRecipes($translationAggregator, $exportData->getCombination()->getRecipes());

        $translationAggregator->optimize();
        $this->translations = $translationAggregator->getTranslations();
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
        $ids = [];
        $translationsByIds = [];
        foreach ($this->translations as $translation) {
            $id = $this->idCalculator->calculateIdOfTranslation($translation);
            $translation->setId($id);

            $ids[] = $id;
            $translationsByIds[$id->toString()] = $translation;
        }

        foreach ($this->translationRepository->findByIds($ids) as $translation) {
            $translationsByIds[$translation->getId()->toString()] = $translation;
        }
        $this->translations = $translationsByIds;
    }

    /**
     * Processes the mods.
     * @param TranslationAggregator $translationAggregator
     * @param array|Mod[] $mods
     */
    protected function processMods(TranslationAggregator $translationAggregator, array $mods): void
    {
        foreach ($mods as $mod) {
            $translationAggregator->add(
                EntityType::MOD,
                $mod->getName(),
                $mod->getTitles(),
                $mod->getDescriptions()
            );
        }
    }

    /**
     * Processes the items.
     * @param TranslationAggregator $translationAggregator
     * @param array|Item[] $items
     */
    protected function processItems(TranslationAggregator $translationAggregator, array $items): void
    {
        foreach ($items as $item) {
            $translationAggregator->add(
                $item->getType(),
                $item->getName(),
                $item->getLabels(),
                $item->getDescriptions()
            );
        }
    }

    /**
     * Processes the machines.
     * @param TranslationAggregator $translationAggregator
     * @param array|Machine[] $machines
     */
    protected function processMachines(TranslationAggregator $translationAggregator, array $machines): void
    {
        foreach ($machines as $machine) {
            $translationAggregator->add(
                EntityType::MACHINE,
                $machine->getName(),
                $machine->getLabels(),
                $machine->getDescriptions()
            );
        }
    }

    /**
     * Processes the recipes.
     * @param TranslationAggregator $translationAggregator
     * @param array|Recipe[] $recipes
     */
    protected function processRecipes(TranslationAggregator $translationAggregator, array $recipes): void
    {
        foreach ($recipes as $recipe) {
            $translationAggregator->add(
                EntityType::RECIPE,
                $recipe->getName(),
                $recipe->getLabels(),
                $recipe->getDescriptions()
            );
        }
    }
    
    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $combination->getTranslations()->clear();
        foreach ($this->translations as $translation) {
            $entityManager->persist($translation);
            $combination->getTranslations()->add($translation);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
//        $this->translationRepository->removeOrphans();
    }
}
