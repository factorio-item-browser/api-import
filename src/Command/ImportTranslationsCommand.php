<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\DBAL\DBALException;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;

/**
 * The command for importing the translations of a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportTranslationsCommand extends AbstractCombinationImportCommand
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
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param ExportDataService $exportDataService
     * @param IdCalculator $idCalculator
     * @param TranslationRepository $translationRepository
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        ExportDataService $exportDataService,
        IdCalculator $idCalculator,
        TranslationRepository $translationRepository
    ) {
        parent::__construct($combinationRepository, $exportDataService);
        $this->idCalculator = $idCalculator;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     * @throws DBALException
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        $translations = $this->process($exportData);
        $this->hydrateIds($translations);
        $this->translationRepository->persistTranslationsToCombination($combination, $translations);
    }

    /**
     * Processes all translations, returning the created entities.
     * @param ExportData $exportData
     * @return array|Translation[]
     */
    public function process(ExportData $exportData): array
    {
        $translationAggregator = new TranslationAggregator();

        $this->processMods($translationAggregator, $exportData->getCombination()->getMods());
        $this->processItems($translationAggregator, $exportData->getCombination()->getItems());
        $this->processMachines($translationAggregator, $exportData->getCombination()->getMachines());
        $this->processRecipes($translationAggregator, $exportData->getCombination()->getRecipes());

        $translationAggregator->optimize();
        return $translationAggregator->getTranslations();
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
     * Hydrates the ids to the translation entities.
     * @param array|Translation[] $translations
     */
    protected function hydrateIds(array $translations): void
    {
        foreach ($translations as $translation) {
            $translation->setId($this->idCalculator->calculateIdOfTranslation($translation));
        }
    }
}
