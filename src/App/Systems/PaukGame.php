<?php

declare(strict_types=1);

namespace App\Systems;

use App\DeckFactory;
use App\Events\CheckInputEvent;
use App\Events\DisplayMainScreenForFirstDataEntry;
use App\Events\DisplayMainScreenMessageEvent;
use App\Events\CreateScreenWithGameRulesEvent;
use App\Events\DisplayMainScreenForSecondDataEntryEvent;
use App\Events\DisplayMainScreenForThirdDataEntryEvent;
use App\Events\DisplayMainScreenWithCompletedInputFormEvent;
use App\Events\DisplayWinnerScreenEvent;
use App\Events\InputEvent;
use App\GameObject\Deck;

class PaukGame extends Game
{
    private array $moves = [];

    private array $previousMoves = [];

    public const
        CANCEL_MOVE = "c",
        SUGGEST = "h",
        DISTRIBUTE = "d";

    private bool $initialized = false;

    private DeckFactory $deckFactory;

    private Deck $mainDeck;

    private int $score = 1000;

    private int $step = 0;
    /**
     * @var Deck[]
     */
    private array $decks;

    private int $completedDecks = 0;

    private bool $isItPossibleToCancelMove = false;

    public function __construct(DeckFactory $deckFactory)
    {
        $this->deckFactory = $deckFactory;
    }

    public function getSubscriptions(): array
    {
        return [
            ...parent::getSubscriptions(),
            InputEvent::class => function (InputEvent $inputEvent) {
                $this->moveManager($inputEvent->getInput());
            },
        ];
    }

    protected function update(): void
    {
        if (!$this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }
    }

    private function initialize(): void
    {
        $this->createDecks();
        $gameData = $this->getGameData();
        $createMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
        $createMainScreenForFirstDataEntry->setGameData($gameData);
        $this->eventPusher->push(
            new CreateScreenWithGameRulesEvent(),
            $createMainScreenForFirstDataEntry, new CheckInputEvent()
        );
    }

    private function createDecks(): void
    {
        $this->mainDeck = $this->deckFactory->createMainDeck();
        $numberOfCards = 6;
        for ($i = 1; $i <= 10; $i++) {
            if ($i > 4) {
                $numberOfCards = 5;
            }
            $decks[] = $this->deckFactory->createDeck($this->mainDeck->giveCards($numberOfCards));
        }
        $this->decks = $decks;
    }

    private function getGameData(): array
    {
        $gameData = [];
        $gameData["score"] = $this->score;
        $gameData["steps"] = $this->step;
        $gameData["decks"] = $this->decks;
        $gameData["mainDeck"] = $this->mainDeck;
        $gameData["completedDecks"] = $this->completedDecks;
        $gameData["moves"] = $this->moves;
        $gameData["previousMoves"] = $this->previousMoves;
        $gameData["isItPossibleToCancelMove"] = $this->isItPossibleToCancelMove;

        return  $gameData;
    }

    public function processingPlayerMoves(): void
    {
        $from = $this->moves[0];
        $numberCard = $this->moves[1];
        $to = $this->moves[2];

        if (is_numeric($from) && is_numeric($numberCard) && is_numeric($to)) {
            $this->updateGameState();
        } else {
            $createMainScreenMessageEvent = new DisplayMainScreenMessageEvent();
            $message = "Invalid input!!!";
            $createMainScreenMessageEvent->setGameData($this->getGameData());
            $createMainScreenMessageEvent->setMessage($message);
            $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push(
                $createMainScreenMessageEvent,
                $displayMainScreenForFirstDataEntry,
                new CheckInputEvent()
            );
            $this->clearMoves();
        }
    }

    private function cardDistributionManager(): void
    {
        if ($this->isItPossibleToDistributeCards()) {
            $this->dealCards();
            $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push($displayMainScreenForFirstDataEntry, new CheckInputEvent());
        } else {
            $createMainScreenMessageEvent = new DisplayMainScreenMessageEvent();
            $createMainScreenMessageEvent->setGameData($this->getGameData());
            $message = " Decks Empty!!!";
            $createMainScreenMessageEvent->setMessage($message);
            $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push($createMainScreenMessageEvent, $displayMainScreenForFirstDataEntry,
                new CheckInputEvent());
        }
        $this->isItPossibleToCancelMove = false;
        $this->clearMoves();
    }

    private function isItPossibleToDistributeCards(): bool
    {
        if ($this->mainDeck->countingCard() !== 0) {
            return true;
        }
        return false;
    }

    function dealCards(): void
    {
        $takeCardForMainDeck = 1;
        $numberOfDeck = 10;

        for ($i = 0; $i < $numberOfDeck; $i++) {
            $card = $this->mainDeck->giveCards($takeCardForMainDeck);
            $this->decks[$i]->addCards($card);
        }
    }

    private function moveCancellationManager(): void
    {
        if ($this->isItPossibleToCancelMove()) {
            $this->cancelMove();
            $displayMainScreenForFirstDataEntry =  new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push($displayMainScreenForFirstDataEntry, new CheckInputEvent());
        } else {
            $displayMainScreenMessageEvent = new DisplayMainScreenMessageEvent();
            $message = "You can't cancel a move now!!!";
            $displayMainScreenMessageEvent->setMessage($message);
            $displayMainScreenMessageEvent->setGameData($this->getGameData());
            $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push(
                $displayMainScreenMessageEvent,
                $displayMainScreenForFirstDataEntry,
                new CheckInputEvent()
            );
        }
        $this->clearMoves();
        $this->clearPreviousMoves();
    }

    public function cancelMove(): void
    {
        $numberOfDeckFromWhichCardWasTaken = $this->previousMoves["from"];
        $numberOfLastCardInDeck = $this->decks[$numberOfDeckFromWhichCardWasTaken]->countingCard() - 1;
        $cards = $this->previousMoves["cards"];
        $numberOfDeckWhereCardWasMoved = $this->previousMoves["to"];
        $isPreviousCardReversed = $this->previousMoves["isCardTurnedOver"];

        if ($numberOfLastCardInDeck > 0) {
            $this->decks[$numberOfDeckFromWhichCardWasTaken]->setStateReversed(
                $numberOfLastCardInDeck,
                $isPreviousCardReversed
            );
        }

        $whereToDelete = $this->decks[$numberOfDeckWhereCardWasMoved]->countingCard() - count($cards);
        $this->decks[$numberOfDeckWhereCardWasMoved]->deletCardsInDeck($whereToDelete);
        $this->decks[$numberOfDeckFromWhichCardWasTaken]->addCards($cards);
    }

    private function isItPossibleToCancelMove(): bool
    {
        $numberOfPreviousMoves = count($this->previousMoves);
        $isItPossibleToCancelMove = $this->isItPossibleToCancelMove;

        if ($numberOfPreviousMoves !== 0 && $isItPossibleToCancelMove) {
            return true;
        }
        return false;
    }

    private function hintManager(): void
    {
        $this->clearMoves();
        $this->setLessScore(100);
        $hint = $this->createHint();
        if ($hint !== false) {
            $from = $hint["from"];
            $typeCard = $hint["type"];
            $to = $hint["to"];
            $message = "You can make a move from $from deck with a $typeCard card to a $to deck!!!";

        } else {
            $message = "Click on the distributor!!!";
        }
        $displayMainScreenMessageEvent = new DisplayMainScreenMessageEvent();
        $displayMainScreenMessageEvent->setGameData($this->getGameData());
        $displayMainScreenMessageEvent->setMessage($message);
        $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
        $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
        $this->eventPusher->push(
            $displayMainScreenMessageEvent,
            $displayMainScreenForFirstDataEntry,
            new CheckInputEvent()
        );
    }

    private function createHint(): array|bool
    {
        $hint = [];
        $cards = [];
        $j = 0;

        $searchCardCanBeTaken = function (Deck $deck) {
            for ($i = 0; $i < $deck->countingCard(); $i++) {
                if ($deck->isCardCanBeTaken($i)) {
                    return $deck->getCard($i);
                }
            }
            return false;
        };

        for ($i = 0; $i < count($this->decks); $i++) {
            $deck = $this->decks[$j];
            if ($i !== $j) {
                $card = $searchCardCanBeTaken($deck);
                if ($searchCardCanBeTaken($deck) !== false) {
                    $cards[] = $card;
                    if ($this->decks[$i]->isCanYouPut($cards)) {
                        $typeCard = $card->getType();
                        $hint["from"] = $j + 1;
                        $hint["type"] = $typeCard;
                        $hint["to"] = $i + 1;
                        return $hint;
                    }
                    $cards = [];
                }
            }
            if ($i === count($this->decks) - 1) {
                $i = -1;
                $j++;
            }
            if ($j === count($this->decks)) {
                return false;
            }
        }
        return  false;
    }

    public function updateGameState(): void
    {
        $this->clearPreviousMoves();
        $isPreviousCardReversed = false;
        $isWin = false;
        $moves = $this->moves;
        $from = (int)$moves[0] -1;
        $numberCard = (int)$moves[1] - 1;
        $to = (int)$moves[2] -1;
        $this->clearMoves();
        $decks = $this->decks;
        try {
            if (!$this->isThereSuchDeckNumber($from)) {
                $from++;
                throw new \Exception("Deck $from does not exist!!!");
            }
            if (!$this->isThereSuchDeckNumber($to)) {
                $to++;
                throw new \Exception("Deck $to does not exist!!!");
            }
            $isTakenCard = $decks[$from]->isCardCanBeTaken($numberCard);
            if (!$isTakenCard) {
                throw new \Exception("This card cannot be taken!!!");
            }
            $card = $decks[$from]->takeCards($numberCard);
            $isCanPut = $decks[$to]->isCanYouPut($card);
            if (!$isCanPut) {
                throw new \Exception("You can't put it here!!!");
            }
            if ($numberCard !== 0) {
                $numberCardAboutWhichWeTake = $numberCard - 1;
                $cardAboutWhichWeTake = $decks[$from]->takeCards($numberCardAboutWhichWeTake);
                $isPreviousCardReversed = $cardAboutWhichWeTake[0]->isReversed();
            }

            $this->previousMoves["from"] = $from;
            $this->previousMoves["numberCard"] = $numberCard;
            $this->previousMoves["to"] = $to;
            $this->previousMoves["cards"] = $card;
            $this->previousMoves["isCardTurnedOver"] = $isPreviousCardReversed;
            $decks[$from]->deletCardsInDeck($numberCard);
            $decks[$to]->addCards($card);
            $this->setLessScore(25);
            $this->setSteps();
            $this->isItPossibleToCancelMove = true;

            if ($this->step > 11) {
                $resultSearch = $this->searchForCompletedDeck();
                if ($resultSearch !== false) {
                    $this->setMoreScore(1000);
                    $keyKing = $decks[$resultSearch]->searchKeyKingInCompletedDeck();
                    $decks[$resultSearch]->deletCardsInDeck($keyKing);
                    $this->setCompletedDecks();
                    $this->isItPossibleToCancelMove = false;
                }
            }

            if ($this->step > 100) {
                $isWin = $this->isGameOver();
                if ($isWin) {
                    $displayWinnerScreenEvent = new DisplayWinnerScreenEvent();
                    $displayWinnerScreenEvent->setGameData($this->getGameData());
                    $this->eventPusher->push($displayWinnerScreenEvent);
                }
            }
            if (!$isWin) {
                $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
                $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
                $this->eventPusher->push($displayMainScreenForFirstDataEntry, new CheckInputEvent());
            }

        }catch (\Exception $message) {
            $message = $message->getMessage();
            $displayMainScreenMessageEvent = new DisplayMainScreenMessageEvent();
            $displayMainScreenMessageEvent->setGameData($this->getGameData());
            $displayMainScreenMessageEvent->setMessage($message);
            $displayMainScreenForFirstDataEntry = new DisplayMainScreenForFirstDataEntry();
            $displayMainScreenForFirstDataEntry->setGameData($this->getGameData());
            $this->eventPusher->push(
                $displayMainScreenMessageEvent,
                $displayMainScreenForFirstDataEntry,
                new CheckInputEvent()
            );
        }
    }

    private function isThereSuchDeckNumber(int $number): bool
    {
        if (isset($this->decks[$number])) {
            return true;
        }
        return false;
    }

    private function isGameOver(): bool
    {
        $numberOfEmptyDecks = null;
        foreach ($this->decks as $deck) {
            if ($deck->isDeckEmpty()) {
                $numberOfEmptyDecks++;
            }
        }
        return $numberOfEmptyDecks === count($this->decks);
    }

    private function searchForCompletedDeck(): int|bool
    {
        foreach ($this->decks as $index => $deck) {
            if ($deck->thisDeckCompleted()) {
                return $index;
            }
        }
        return false;
    }

    private function setSteps(): void
    {
        $this->step++;
    }

    private function setLessScore(int $scoreReceived): void
    {
        $this->score -= $scoreReceived;
    }

    private function setMoreScore(int $scoreReceived): void
    {
        $this->score += $scoreReceived;
    }


    private function setCompletedDecks(): void
    {
        $this->completedDecks++;
    }

    private function moveManager(string $input): void
    {
        $input = \mb_strtolower($input);
        $this->setMoves($input);
        if (count($this->moves) === 1) {
            if ($this->thisIsCommand($input)) {
                $this->executePlayersCommand($input);
            } else {
                $displayMainScreenForSecondDataEntryEvent = new DisplayMainScreenForSecondDataEntryEvent();
                $displayMainScreenForSecondDataEntryEvent->setGameData($this->getGameData());
                $this->eventPusher->push($displayMainScreenForSecondDataEntryEvent, new CheckInputEvent());
            }
        }

        if (count($this->moves) === 2) {
            $displayMainScreenForThirdDataEntryEvent = new DisplayMainScreenForThirdDataEntryEvent();
            $displayMainScreenForThirdDataEntryEvent->setGameData($this->getGameData());
            $this->eventPusher->push($displayMainScreenForThirdDataEntryEvent, new CheckInputEvent());
        }

        if (count($this->moves) === 3)
        {
            $displayMainScreenWithCompletedInputFormEvent = new DisplayMainScreenWithCompletedInputFormEvent();
            $displayMainScreenWithCompletedInputFormEvent->setGameData($this->getGameData());
            $this->eventPusher->push($displayMainScreenWithCompletedInputFormEvent);
            $this->processingPlayerMoves();
        }
    }

    private function thisIsCommand(string $command): bool
    {
        if ($command === self::DISTRIBUTE) {
            return true;
        }

        if ($command === self::CANCEL_MOVE) {
            return true;
        }

        if ($command === self::SUGGEST) {
            return true;
        }
        return false;
    }

    private function executePlayersCommand(string $command): void
    {
        if ($command === self::DISTRIBUTE) {
            $this->cardDistributionManager();
        }
        if ($command === self::CANCEL_MOVE) {
            $this->moveCancellationManager();
        }
        if ($command === self::SUGGEST) {
            $this->hintManager();
        }
    }

    private function clearMoves(): void
    {
        $this->moves = [];
    }

    private function setMoves(string $input): void
    {
        $this->moves[] = $input;
    }

    private function clearPreviousMoves(): void
    {
        $this->previousMoves = [];
    }

}

