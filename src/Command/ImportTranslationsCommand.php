<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\DBAL\DBALException;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
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
class ImportTranslationsCommand extends AbstractImportCommand
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
     * @param Console $console
     * @param ExportDataService $exportDataService
     * @param IdCalculator $idCalculator
     * @param TranslationRepository $translationRepository
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService,
        IdCalculator $idCalculator,
        TranslationRepository $translationRepository
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);
        $this->idCalculator = $idCalculator;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::IMPORT_TRANSLATIONS);
        $this->setDescription('Imports the translations of a combination.');
    }

    /**
     * Returns a label describing what the import is doing.
     * @return string
     */
    protected function getLabel(): string
    {
        return 'Processing the translations';
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

        $this->console->writeAction('Persisting processed data');
        $this->translationRepository->persistTranslationsToCombination($combination->getId(), $translations);
    }

    /**
     * Processes all translations, returning the created entities.
     * @param ExportData $exportData
     * @return array|Translation[]
     */
    public function process(ExportData $exportData): array
    {
        $translationAggregator = $this->createTranslationAggregator();

        $this->processMods($translationAggregator, $exportData->getCombination()->getMods());
        $this->processItems($translationAggregator, $exportData->getCombination()->getItems());
        $this->processMachines($translationAggregator, $exportData->getCombination()->getMachines());
        $this->processRecipes($translationAggregator, $exportData->getCombination()->getRecipes());

        $translationAggregator->optimize();
        return $translationAggregator->getTranslations();
    }

    /**
     * Creates the translation aggregator to use.
     * @return TranslationAggregator
     */
    protected function createTranslationAggregator(): TranslationAggregator
    {
        return new TranslationAggregator();
    }

    /**
     * Processes the mods.
     * @param TranslationAggregator $translationAggregator
     * @param array|Mod[] $mods
     */
    protected function processMods(TranslationAggregator $translationAggregator, array $mods): void
    {
        $this->console->writeAction('Processing mods');
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
        $this->console->writeAction('Processing items');
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
        $this->console->writeAction('Processing machines');
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
        $this->console->writeAction('Processing recipes');
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
