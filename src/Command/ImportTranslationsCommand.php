<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Constant\ParameterName;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use Ramsey\Uuid\Uuid;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportTranslationsCommand implements CommandInterface
{
    /**
     * The combination repository.
     * @var CombinationRepository
     */
    protected $combinationRepository;

    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

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
     * ImportTranslationsCommand constructor.
     * @param CombinationRepository $combinationRepository
     * @param ExportDataService $exportDataService
     * @param EntityManagerInterface $entityManager
     * @param IdCalculator $idCalculator
     * @param TranslationRepository $translationRepository
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        ExportDataService $exportDataService,
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        TranslationRepository $translationRepository
    ) {
        $this->combinationRepository = $combinationRepository;
        $this->exportDataService = $exportDataService;
        $this->entityManager = $entityManager;
        $this->idCalculator = $idCalculator;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     * @throws DBALException
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
    {
        $combinationId = $route->getMatchedParam(ParameterName::COMBINATION, '');
        $exportData = $this->exportDataService->loadExport($combinationId);
        $combination = $this->combinationRepository->findById(Uuid::fromString($combinationId));

        $translations = $this->process($exportData);
        $this->hydrateIds($translations);
        $this->translationRepository->persistTranslationsToCombination($combination, $translations);

        return 0;
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
