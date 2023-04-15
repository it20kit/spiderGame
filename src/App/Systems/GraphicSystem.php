<?php

namespace App\Systems;

use App\Events\DisplayMainScreenForFirstDataEntry;
use App\Events\DisplayMainScreenMessageEvent;
use App\Events\CreateScreenWithGameRulesEvent;
use App\Events\DisplayMainScreenForSecondDataEntryEvent;
use App\Events\DisplayMainScreenForThirdDataEntryEvent;
use App\Events\DisplayMainScreenWithCompletedInputFormEvent;
use App\Events\DisplayWinnerScreenEvent;
use App\Events\Event;
use App\Painter\Painter;
use App\ProjectManager;

class GraphicSystem extends AbstractSystem
{
    private Painter $painter;

    private ProjectManager $projectManager;

    public function __construct(Painter $painter, ProjectManager $projectManager)
    {
        $this->painter = $painter;
        $this->projectManager = $projectManager;
    }

    public function getSubscriptions(): array
    {
        return [
            CreateScreenWithGameRulesEvent::class => fn() => $this->createScreenWithGameRules(),
            DisplayMainScreenMessageEvent::class => function(Event $event) {
                $this->createMainScreenMessage($event->getGameData(), $event->getMessage());
            },
            DisplayMainScreenForFirstDataEntry::class => function(Event $event) {
                $this->createMainScreenForFirstDataEntry($event->getGameData());
            },
            DisplayMainScreenForSecondDataEntryEvent::class => function(Event $event) {
                $this->createMainScreenForSecondDataEntry($event->getGameData());
            },
            DisplayMainScreenForThirdDataEntryEvent::class => function(Event $event) {
                $this->createMainScreenForThirdDataEntry($event->getGameData());
            },
            DisplayMainScreenWithCompletedInputFormEvent::class => function(Event $event) {
                $this->createMainScreenWithCompletedInputForm($event->getGameData());
            },
            DisplayWinnerScreenEvent::class => function(Event $event) {
                $this->createWinnerScreen($event->getGameData());
            }
        ];
    }

    public function createMainScreen(array $gameData): void
    {
        $firstXCoordinatesForDisplayingCardNumbersOnScreen = 2;
        $secondXCoordinatesForDisplayingCardNumbersOnScreen = 147;
        $this->addDecksInScreen($gameData);
        $this->addNumberDeckInScreen();
        $this->addNumberCardInScreen($firstXCoordinatesForDisplayingCardNumbersOnScreen);
        $this->addNumberCardInScreen($secondXCoordinatesForDisplayingCardNumbersOnScreen);
        $this->addCompletedDeckInScreen($gameData);
        $this->addMainDeckInScreen($gameData);
        $this->addCounterStepsInScreen($gameData);
        $this->addCounterScoreInScreen($gameData);
    }

    public function createScreenWithGameRules(): void
    {
        $message = "START GAME";
        $xCoordinateForMessageOutput = 80;
        $yCoordinateForMessageOutput = 20;
        $this->painter->addPicture($message, $xCoordinateForMessageOutput, $yCoordinateForMessageOutput);
        $this->painter->display();
        sleep(3);
        $this->painter->clear();
    }

    private function createMainScreenForFirstDataEntry(array $gameData): void
    {
        $this->painter->clear();
        $this->createMainScreen($gameData);
        $this->addListOfCommandsToScreen();
        $message = "Enter the number of the deck from which you want to get a card or enter the command:";
        $xCoordinateForMessageOutput = 6;
        $yCoordinateForMessageOutput = 40;
        $this->painter->addPicture($message, $xCoordinateForMessageOutput, $yCoordinateForMessageOutput);
        $this->painter->display();
    }

    private function createMainScreenForSecondDataEntry(array $gameData): void
    {
        $from = $gameData["moves"][0];
        $this->painter->clear();
        $this->createMainScreen($gameData);
        $firstMessage = "Enter the number of the deck from which you want to get a card or enter the command:$from";
        $xCoordinateForFirstMessageOutput = 6;
        $yCoordinateForFirstMessageOutput = 40;
        $secondMessage = "Enter the number of the card you want to take:";
        $xCoordinateForSecondMessageOutput = 6;
        $yCoordinateForSecondMessageOutput = 42;
        $this->painter->addPicture($firstMessage, $xCoordinateForFirstMessageOutput,$yCoordinateForFirstMessageOutput);
        $this->painter->addPicture($secondMessage,$xCoordinateForSecondMessageOutput,$yCoordinateForSecondMessageOutput);
        $this->painter->display();
    }

    private function createMainScreenForThirdDataEntry(array $gameData): void
    {
        $from = $gameData["moves"][0];
        $numberCard = $gameData["moves"][1];
        $firstMessage = "Enter the number of the deck from which you want to get a card or enter the command:$from";
        $xCoordinateForFirstMessageOutput = 6;
        $yCoordinateForFirstMessageOutput = 40;
        $secondMessage = "Enter the number of the card you want to take:$numberCard";
        $xCoordinateForSecondMessageOutput = 6;
        $yCoordinateForSecondMessageOutput = 42;
        $thirdMessage = "Enter the number of the deck where to transfer the cards:";
        $xCoordinateForThirdMessageOutput = 6;
        $yCoordinateForThirdMessageOutput = 44;
        $this->painter->clear();
        $this->createMainScreen($gameData);
        $this->painter->addPicture($firstMessage,$xCoordinateForFirstMessageOutput,$yCoordinateForFirstMessageOutput);
        $this->painter->addPicture($secondMessage,$xCoordinateForSecondMessageOutput,$yCoordinateForSecondMessageOutput);
        $this->painter->addPicture($thirdMessage,$xCoordinateForThirdMessageOutput,$yCoordinateForThirdMessageOutput);
        $this->painter->display();
    }

    private function createMainScreenWithCompletedInputForm(array $gameData): void
    {
        $from = $gameData["moves"][0];
        $numberCard = $gameData["moves"][1];
        $to = $gameData["moves"][2];
        $firstMessage = "Enter the number of the deck from which you want to get a card or enter the command:$from";
        $xCoordinateForFirstMessageOutput = 6;
        $yCoordinateForFirstMessageOutput = 40;
        $secondMessage = "Enter the number of the card you want to take:$numberCard";
        $xCoordinateForSecondMessageOutput = 6;
        $yCoordinateForSecondMessageOutput = 42;
        $thirdMessage = "Enter the number of the deck where to transfer the cards:$to";
        $xCoordinateForThirdMessageOutput = 6;
        $yCoordinateForThirdMessageOutput = 44;
        $this->painter->clear();
        $this->painter->addPicture($firstMessage,$xCoordinateForFirstMessageOutput,$yCoordinateForFirstMessageOutput);
        $this->painter->addPicture($secondMessage,$xCoordinateForSecondMessageOutput,$yCoordinateForSecondMessageOutput);
        $this->painter->addPicture($thirdMessage,$xCoordinateForThirdMessageOutput,$yCoordinateForThirdMessageOutput);
        $this->painter->display();
    }

    public function createMainScreenMessage(array $gameData, string $message): void
    {
        $this->painter->clear();
        $this->createMainScreen($gameData);
        $this->createWindowMessage($message);
        $this->painter->display();
        sleep(3);
        $this->painter->clear();
    }


    private function createWindowMessage(string $message): void
    {
        $sizeMessage = strlen($message);

        $createUpperFrameDependingOnSizeOfMessage = function (int $sizeMessage, string $symbol): string
        {
            $times = $sizeMessage + 10;
            return str_repeat($symbol,$times);
        };

        $createLateralBorder = function (string $symbol): string
        {
            $times = 10;
            $symbol .= "\n";
            return str_repeat($symbol, $times);
        };

        $calculateCoordinatesXOfMessageSize = function (
            int $sizeMessage,
            int $xCoordinateForAddingLateralBorderLeftToScreen
        ): int
        {
            $constIndent = 9;
            return $xCoordinateForAddingLateralBorderLeftToScreen + $sizeMessage + $constIndent;
        };


        $xCoordinateForAddingBorderUpToScreen = 48;
        $yCoordinateForAddingBorderUpToScreen = 35;
        $xCoordinateForAddingMessageToScreen = 51;
        $yCoordinateForAddingMessageToScreen = 40;
        $xCoordinateForAddingBorderDownToScreen = 48;
        $yCoordinateForAddingBorderDownToScreen = 45;
        $xCoordinateForAddingLateralBorderLeftToScreen = 48;
        $yCoordinateForAddingLateralBorderLeftToScreen = 35;
        $xCoordinateForAddingLateralBorderRightToScreen = $calculateCoordinatesXOfMessageSize(
            $sizeMessage,
            $xCoordinateForAddingLateralBorderLeftToScreen
        );
        $yCoordinateForAddingLateralBorderRightToScreen = 35;
        $symbol = "*";

        $borderErrorUp = $createUpperFrameDependingOnSizeOfMessage($sizeMessage, $symbol);
        $borderErrorDown = $borderErrorUp;
        $lateralBorder = $createLateralBorder($symbol);

        $this->painter->addPicture($borderErrorUp, $xCoordinateForAddingBorderUpToScreen,
            $yCoordinateForAddingBorderUpToScreen
        );
        $this->painter->addPicture(
            $message,
            $xCoordinateForAddingMessageToScreen,
            $yCoordinateForAddingMessageToScreen
        );
        $this->painter->addPicture(
            $borderErrorDown,
            $xCoordinateForAddingBorderDownToScreen,
            $yCoordinateForAddingBorderDownToScreen
        );
        $this->painter->addPicture(
            $lateralBorder,
            $xCoordinateForAddingLateralBorderLeftToScreen,
            $yCoordinateForAddingLateralBorderLeftToScreen
        );
        $this->painter->addPicture(
            $lateralBorder,
            $xCoordinateForAddingLateralBorderRightToScreen,
            $yCoordinateForAddingLateralBorderRightToScreen
        );
    }

    private function addMainDeckInScreen(array $gameData): void
    {
        $xCoordinateForAddingMainDeckToScreen = 150;
        $yCoordinateForAddingMainDeckToScreen = 4;
        $xCoordinateForAddingCardInDeckToScreen = 150;
        $yCoordinateForAddingCardInDeckToScreen = 3;
        $mainDeck = $gameData["mainDeck"];
        $numberOfCardInDeck = $mainDeck->countingCard();
        $numberOfDecksOfTenPieces = $numberOfCardInDeck / 10;
        $message = "Card in MainDeck: $numberOfCardInDeck";
        $this->painter->addPicture(
            $message,
            $xCoordinateForAddingCardInDeckToScreen,
            $yCoordinateForAddingCardInDeckToScreen
        );

        for ($i = 1; $i <= $numberOfDecksOfTenPieces; $i++) {
            $this->painter->addPicture($this->projectManager->toStringCardReversed(),
                $xCoordinateForAddingMainDeckToScreen, $yCoordinateForAddingMainDeckToScreen);
            $xCoordinateForAddingMainDeckToScreen +=3;
        }
    }

    private function addCompletedDeckInScreen(array $gameData): void
    {
        $sample = "
 ________
| K      |
|        |
|        |
|        |
|      K |
|________|";
        $xCoordinateForAddingSampleToScreen = 150;
        $yCoordinateForAddingSampleToScreen = 36;
        $numberOfCompletedDecks = $gameData["completedDecks"];
        if ($numberOfCompletedDecks !== 0) {
            for ($i = 1; $i <= $numberOfCompletedDecks; $i++) {
                $this->painter->addPicture(
                    $sample,
                    $xCoordinateForAddingSampleToScreen,
                    $yCoordinateForAddingSampleToScreen
                );
                $xCoordinateForAddingSampleToScreen+= 3;
            }
        }
    }

    public function createWinnerScreen(array $gameData): void
    {
        $this->painter->clear();
        $xCoordinateForAddingFirstMessageToScreen = 69;
        $yCoordinateForAddingFirsMessageToScreen = 38;
        $xCoordinateForAddingSecondMessageToScreen = 70;
        $yCoordinateForAddingSecondMessageToScreen = 36;
        $xCoordinateForAddingPictureToScreen = 60;
        $yCoordinateForAddingPictureToScreen = 40;
        $score = $gameData["score"];
        $step = $gameData["steps"];
        $sample = "
  \                 /     0         |\    |
   \      /\       /      |         | \   |
    \    /  \     /       |         |  \  |
     \  /    \   /        |         |   \ |
      \/      \ /         |         |    \|
    ";
        $this->addNumberDeckInScreen();
        $this->addWinningDecksInScreen();
        $this->painter->addPicture($sample, $xCoordinateForAddingPictureToScreen, $yCoordinateForAddingPictureToScreen);
        $this->painter->addPicture("You have scored $score points!!!", $xCoordinateForAddingFirstMessageToScreen,
        $yCoordinateForAddingFirsMessageToScreen);
        $this->painter->addPicture("You have taken $step steps!!!", $xCoordinateForAddingSecondMessageToScreen,
        $yCoordinateForAddingSecondMessageToScreen);
        $this->painter->display();
    }

    private function addWinningDecksInScreen(): void
    {
        $sample = "
 ________ 
| K      |
 ________
| D      |
 ________
| B      |
 ________ 
| t      |
 ________
| 9      |
 ________ 
| 8      |
 ________
| 7      |
 ________
| 6      |
 ________ 
| 5      |
 _________
| 4      |
 ________
| 3      |
 ________
| 2      |
 ________
| T      |
|        |
|        |
|      T |
|________|

        ";
        $xCoordinateForAddingSampleToScreen = 8;
        $yCoordinateForAddingSampleToScreen = 3;
        $numberOfSample = 10;

        for ($i = 1; $i <= $numberOfSample; $i++) {
            $this->painter->addPicture($sample, $xCoordinateForAddingSampleToScreen, $yCoordinateForAddingSampleToScreen);
            $xCoordinateForAddingSampleToScreen += 14;
        }
    }


    private function addNumberDeckInScreen(): void
    {
        $xCoordinateForAddingNumberDecksToScreen = 12;
        $yCoordinateForAddingNumberDecksToScreen = 2;
        for ($i =  1; $i <= 10; $i++) {
            $i = (string)$i;
            $this->painter->addPicture(
                $i,
                $xCoordinateForAddingNumberDecksToScreen,
                $yCoordinateForAddingNumberDecksToScreen
            );
            $xCoordinateForAddingNumberDecksToScreen += 14;
            $i = (int)$i;
        }
    }

    private function addNumberCardInScreen(int $xCoordinateForAddingNumberCardsToScreen): void
    {
        $yCoordinateForAddingNumberCardsToScreen = 4;
        for ($i = 1; $i <= 20; $i++) {
            $i = (string)$i;
            $this->painter->addPicture(
                $i,
                $xCoordinateForAddingNumberCardsToScreen,
                $yCoordinateForAddingNumberCardsToScreen
            );
            $yCoordinateForAddingNumberCardsToScreen += 2;
            $i = (int)$i;
        }
    }

    private function addDecksInScreen(array $gameData): void
    {
        $decks = $gameData["decks"];
        $xCoordinateForAddingDecksToScreen = 8;
        $yCoordinateForAddingDecksToScreen = 3;

        foreach ($decks as $deck) {
            $picture = $this->projectManager->getStringDeckRepresentation($deck);
            $this->painter->addPicture($picture, $xCoordinateForAddingDecksToScreen,$yCoordinateForAddingDecksToScreen);
            $xCoordinateForAddingDecksToScreen += 14;
        }
    }

    private function addCounterStepsInScreen(array $gameData): void
    {
        $xCoordinateForAddingStepsToScreen = 150;
        $yCoordinateForAddingStepsToScreen = 12;
        $steps = $gameData["steps"];
        $string = "Steps taken:$steps";
        $this->painter->addPicture($string,$xCoordinateForAddingStepsToScreen,$yCoordinateForAddingStepsToScreen);
    }

    private function addCounterScoreInScreen(array $gameData): void
    {
        $xCoordinateForAddingScoreToScreen = 150;
        $yCoordinateForAddingScoreToScreen = 14;
        $score = $gameData["score"];
        $string = "Score:$score";
        $this->painter->addPicture($string, $xCoordinateForAddingScoreToScreen, $yCoordinateForAddingScoreToScreen);
    }

    private function addListOfCommandsToScreen(): void
    {
        $heading = "Available commands:";
        $xCoordinateHeading = 155;
        $yCoordinateHeading = 18;
        $textOfFirstCommand = "Enter 'd' to deal the cards!!!";
        $xCoordinateFirstCommand = 150;
        $yCoordinateFirstCommand = 20;
        $textOfSecondCommand = "Enter 'h' to get a hint!!!";
        $xCoordinateSecondCommand = 150;
        $yCoordinateSecondCommand = 23;
        $textOfThirdCommand = "Enter 'c' to cancel the move!!!";
        $xCoordinateThirdCommand = 150;
        $yCoordinateThirdCommand = 26;

        $this->painter->addPicture($heading, $xCoordinateHeading, $yCoordinateHeading);
        $this->painter->addPicture($textOfFirstCommand, $xCoordinateFirstCommand, $yCoordinateFirstCommand);
        $this->painter->addPicture($textOfSecondCommand, $xCoordinateSecondCommand, $yCoordinateSecondCommand);
        $this->painter->addPicture($textOfThirdCommand, $xCoordinateThirdCommand, $yCoordinateThirdCommand);
    }

}

