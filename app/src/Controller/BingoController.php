<?php

namespace App\Controller;

use App\Entity\Bingo;
use App\Entity\BingoTemplate;
use App\Entity\User;
use App\Form\BingoTemplateType;
use App\Repository\BingoRepository;
use App\Repository\BingoTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bingo')]
class BingoController extends AbstractController
{
    #[Route('/', name: 'app_bingo_index', methods: ['GET'])]
    public function bingo(BingoRepository $bingoRepository, BingoTemplateRepository $templateRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $bingo = $bingoRepository->findLast($user);
        if (!$bingo instanceof Bingo) {
            $bingoTemplate = $templateRepository->findAll()[0];
            if (!$bingoTemplate instanceof BingoTemplate) {
                $this->addFlash('error', 'Das Template is nicht fertig bearbeitet');
            }
            $bingo = new Bingo($user, $bingoTemplate);
        }

        $bingoRepository->save($bingo, true);

        return $this->render('bingo/bingo.html.twig', [
            'currentGame' => $bingo,
        ]);
    }

    #[Route('/toggle', name: 'app_bingo_toggle', methods: ['POST'])]
    public function update(Request $request, BingoRepository $bingoRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $bingo = $bingoRepository->findOneBy(['id' => $data['id']]);
        $bingo->toggle($data['index']);
        $bingoRepository->save($bingo, true);

        return $this->json(['status' => 'success']);
    }

    #[Route('/reset', name: 'app_bingo_reset', methods: ['GET'])]
    public function reset(Request $request, BingoRepository $bingoRepository): Response
    {
        $game = $bingoRepository->find($request->get('id'));
        $bingoRepository->remove($game, true);

        return $this->redirectToRoute('app_bingo_index');
    }

    #[Route('/template/edit', name: 'app_template_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BingoTemplateRepository $bingoTemplateRepository): Response
    {
        $bingoTemplate = $bingoTemplateRepository->findAll()[0] ?? new BingoTemplate();

        $form = $this->createForm(BingoTemplateType::class, $bingoTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = [];
            for ($i = 0; $i < $bingoTemplate->getSize() * $bingoTemplate->getSize(); $i++) {
                $content[] = $_POST['bingo_template']['cell_' . $i] ?? '';
            }
            $bingoTemplate->setContent($content);

            $bingoTemplateRepository->save($bingoTemplate, true);

            return $this->redirectToRoute('app_template_edit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bingo/edit.html.twig', [
            'template' => $bingoTemplate,
            'form' => $form,
        ]);
    }
}
