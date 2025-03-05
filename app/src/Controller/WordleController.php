<?php

namespace App\Controller;

use App\Entity\WordleGame;
use App\Entity\WordleGuess;
use App\Entity\WordleSolution;
use App\Form\WordleSolutionType;
use App\Repository\UserRepository;
use App\Repository\WordleGameRepository;
use App\Repository\WordleSolutionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wordle')]
class WordleController extends AbstractController
{
    #[Route('/', name: 'app_wordle_index', methods: ['GET'])]
    public function wordle(
        Request $request,
        WordleGameRepository $gameRepository,
        WordleSolutionRepository $solutionRepository
    ): Response
    {
        $player = $this->getUser();

        $date = new DateTime();

        $minDate = new DateTime('2022-03-04');
        $tomorrow = new DateTime('tomorrow');
        $today = new DateTime('today');

        $unix = $request->get('unix');
        if ($unix) {
            $date->setTimestamp($unix);
        }

        if ($date < $minDate) {
            $date = $minDate;
        } elseif ($date >= $tomorrow) {
            $date = $today;
        }

        $date->setTime(0, 0);

        $solution = $solutionRepository->findOneBy(['createdAt' => $date]);
        if (!$solution) {
            $solution = $this->initializeSolution($date);
            $solutionRepository->save($solution, true);
        }

        $game = $gameRepository->findGameWithSolutionAndGuesses($solution, $player->getId());
        if (!$game) {
            $game = new WordleGame($solution);
            $game->setPlayer($player);
            $solution->addGame($game);
            $gameRepository->save($game, true);
        }

        $params = [
            'days' => $this->getWordleDays(),
            'unix' => $date->getTimestamp(),
            'currentGame' => $game,
            'solution' => $solution,
        ];

        $params['random'] = false;
        if ($request->get('isRandom')) {
            $params['random'] = $date->getTimestamp();
        }

        if ($game->isFinished()) {
            $userId = $player->getId();
            $games = $gameRepository->findAllGamesWithGuessesForPlayer($userId);
            $guessesNeeded = [];
            foreach ($games as $game) {
                if ($game->isSolved()) {
                    $guessesNeeded[] = count($game->getGuesses());
                } else if ($game->isFinished()) {
                    $guessesNeeded[] = 7;
                }
            }
            $params['games'] = $games;
            $params['guessesNeeded'] = $guessesNeeded;

            $randomSolution = $solutionRepository->findRandomStartedGame($player->getId());
            if (!$randomSolution instanceof WordleSolution) {
                $randomSolution = $solutionRepository->findRandomUnattemptedSolvedGame($player->getId());
            }
            if (!$randomSolution instanceof WordleSolution) {
                $randomSolution = $solutionRepository->findRandomGameThatHasNotBeenStarted($player->getId());
            }

            if ($randomSolution instanceof WordleSolution) {
                $createdAt = $randomSolution->getCreatedAt();
                $params['randomUnix'] = $createdAt->getTimestamp() + 7200;
            }
        }

        return $this->render('wordle/wordle.html.twig', $params);
    }

    #[Route('/guess', name: 'app_wordle_guess', methods: ['GET'])]
    public function wordleGuess(
        Request $request,
        EntityManagerInterface $entityManager,
        int $difficulty = 0
    ): Response
    {
        $date = new DateTime();
        $date->setTimestamp($request->get('unix'));

        /** @var WordleSolution $solution */
        $solution = $entityManager->getRepository(WordleSolution::class)->findOneBy(['createdAt' => $date]);

        /** @var WordleGame $game */
        $game = $entityManager->getRepository(WordleGame::class)->findOneBy([
            'solution' => $solution,
            'player' => $this->getUser()
        ]);

        $words = $this->getWords($difficulty);
        if ($game->isSolved() || !in_array($request->get("guess"), $words)) {
            return new Response("Invalid guess");
        }

        $response = $this->compareGuess(
            str_split($request->get("guess")),
            str_split($solution->getCorrectWord())
        );

        $guess = new WordleGuess($game, $request->get("guess"), $response['solved']);
        $guess->setInfo($response);
        $game->addGuess($guess);

        $isSolved = $guess->isCorrect();
        $isFinished = $guess->isCorrect() || count($game->getGuesses()) >= 6;

        $game->setIsSolved($isSolved);
        $game->setIsFinished($isFinished);
        $response['finished'] = $isFinished;

        $entityManager->persist($game);
        $entityManager->persist($guess);
        $entityManager->flush();

        return new JsonResponse($response);
    }

    #[Route('/guess', name: 'app_wordle_finish_all', methods: ['GET'])]
    public function wordleFinishAll(EntityManagerInterface $entityManager): Response
    {
        $games = $entityManager->getRepository(WordleGame::class)->findAll();

        foreach ($games as $game) {
            $game->setIsFinished(true);
            $entityManager->persist($game);
        }
        $entityManager->flush();

        return $this->redirectToRoute('games');
    }

    #[Route('/guess', name: 'app_wordle_reset', methods: ['GET'])]
    public function wordleReset(Request $request, EntityManagerInterface $entityManager): Response
    {
        $game = $entityManager->getRepository(WordleGame::class)->find($request->get('gameId'));

        $entityManager->remove($game);
        $entityManager->flush();

        return $this->redirectToRoute('games');
    }

    private function getWordleDays(): array
    {
        $days = [];
        for ($i = 5; $i > 0; $i--) {
            $time = strtotime(sprintf("-%s days", $i));
            $days[date('d. M', $time)] = date($time);
        }
        $days['today'] = (new DateTime())->getTimestamp();

        return $days;
    }

    /**
     * @param array $guess
     * @param array $solution
     * @return array
     */
    private function compareGuess(array $guess, array $solution): array
    {
        $response = [
            'solved' => $guess === $solution,
            0 => null,
            1 => null,
            2 => null,
            3 => null,
            4 => null,
        ];

        $size = count($solution);
        $indices = range(0, $size - 1);

        for ($i = 0; $i < $size; $i++) {
            if ($guess[$i] === $solution[$i]) {
                unset($guess[$i]);
                unset($solution[$i]);
                unset($indices[$i]);
                $response[$i] = 'correct';
            }
        }

        foreach ($guess as $key => $letter) {
            if (in_array($letter, $solution)) {
                $response[$key] = 'present';
                unset($solution[array_search($letter, $solution, true)]);
            } else {
                $response[$key] = 'absent';
            }
        }

        return $response;
    }

    /**
     * @param DateTime $date
     * @return WordleSolution
     */
    private function initializeSolution(DateTime $date): WordleSolution
    {
        $possibleSolutions = json_decode(file_get_contents(__DIR__ . "/../../public/games/solutions.json"));

        $start = new DateTime("2022-02-02 01:00:00");
        $today = $date;
        $today->setTime(0, $today->format('i'), $today->format('s'));

        $timeDifference = 1000 * ($today->getTimestamp() - $start->getTimestamp());
        $wordNumber = (round($timeDifference / 864e5) + 228) % 1240;

        return new WordleSolution($possibleSolutions[$wordNumber], $date);
    }

    /**
     * @param int $difficulty
     * @return array
     */
    private function getWords(int $difficulty): array
    {
        switch ($difficulty) {
            case 1:
                return json_decode(file_get_contents(__DIR__ . "/../../public/games/simple_words.json"));
            case 2:
                return json_decode(file_get_contents(__DIR__ . "/../../public/games/extended_words.json"));
            case 3:
                return json_decode(file_get_contents(__DIR__ . "/../../public/games/crazy_words.json"));
            default:
                $arr1 = json_decode(file_get_contents(__DIR__ . "/../../public/games/words.json"));
                $arr2 = json_decode(file_get_contents(__DIR__ . "/../../public/games/simple_words.json"));
                $arr3 = json_decode(file_get_contents(__DIR__ . "/../../public/games/solutions.json"));

                return array_unique(array_values(array_merge($arr1, $arr2, $arr3)));
        }
    }

    //#[Route('/', name: 'app_wordle_index', methods: ['GET'])]
    public function index(WordleSolutionRepository $wordleSolutionRepository): Response
    {
        return $this->render('wordle/index.html.twig', [
            'wordle_solutions' => $wordleSolutionRepository->findAll(),
        ]);
    }

    //#[Route('/new', name: 'app_wordle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, WordleSolutionRepository $wordleSolutionRepository): Response
    {
        $wordleSolution = new WordleSolution();
        $form = $this->createForm(WordleSolutionType::class, $wordleSolution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wordleSolutionRepository->save($wordleSolution, true);

            return $this->redirectToRoute('app_wordle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('wordle/new.html.twig', [
            'wordle_solution' => $wordleSolution,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_wordle_show', methods: ['GET'])]
    public function show(WordleSolution $wordleSolution): Response
    {
        return $this->render('wordle/show.html.twig', [
            'wordle_solution' => $wordleSolution,
        ]);
    }

    //#[Route('/{id}/edit', name: 'app_wordle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        WordleSolution $wordleSolution,
        WordleSolutionRepository $wordleSolutionRepository
    ): Response
    {
        $form = $this->createForm(WordleSolutionType::class, $wordleSolution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wordleSolutionRepository->save($wordleSolution, true);

            return $this->redirectToRoute('app_wordle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('wordle/edit.html.twig', [
            'wordle_solution' => $wordleSolution,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_wordle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        WordleSolution $wordleSolution,
        WordleSolutionRepository $wordleSolutionRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $wordleSolution->getId(), $request->request->get('_token'))) {
            $wordleSolutionRepository->remove($wordleSolution, true);
        }

        return $this->redirectToRoute('app_wordle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/compare/{opponentId}', name: 'app_wordle_user', methods: ['GET'])]
    public function compareUser(
        Request $request,
        WordleGameRepository $gameRepository,
        UserRepository $userRepository
    ): Response
    {
        $opponent = $userRepository->find($request->get('opponentId'));

        $games = $gameRepository->findAllFinishedGamesForPlayer($this->getUser()->getId());
        $gamesOpponent = $gameRepository->findAllFinishedGamesForPlayer($request->get('opponentId'));

        $guesses = [];
        foreach ($games as $game) {
            $guesses[] = $game->isSolved() ? count($game->getGuesses()) : 7;
        }

        $guessesOpponent = [];
        foreach ($gamesOpponent as $game) {
            $guessesOpponent[] = $game->isSolved() ? count($game->getGuesses()) : 7;
        }

        return $this->render('wordle/compare.html.twig', [
            'guesses' => $guesses,
            'guessesOpponent' => $guessesOpponent,
            'opponent' => $opponent,
        ]);
    }
}
