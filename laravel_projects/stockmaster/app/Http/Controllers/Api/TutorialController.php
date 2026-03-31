<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\TutorialProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorialController extends Controller
{
    private function normalizeTags($tags): array
    {
        if (is_array($tags)) {
            return $tags;
        }

        if (is_string($tags) && $tags !== '') {
            $decoded = json_decode($tags, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeDifficulty($value): string
    {
        return match ((int) $value) {
            1 => 'Kezdő',
            2 => 'Haladó',
            3 => 'Profi',
            default => 'Ismeretlen',
        };
    }

    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id() ?: (int) $request->query('user_id', 0);
        $level = (int) $request->query('level', 0);

        $query = Tutorial::query()->orderBy('ID');

        if ($level > 0) {
            $query->where('DifficultyLevel', $level);
        }

        $tutorials = $query->get()->map(function ($tutorial) use ($userId) {
            $progress = null;

            if ($userId > 0) {
                $progress = TutorialProgress::where('UserID', $userId)
                    ->where('TutorialID', $tutorial->ID)
                    ->first();
            }

            return [
                'id' => $tutorial->ID,
                'title' => $tutorial->Title,
                'difficulty' => $this->normalizeDifficulty($tutorial->DifficultyLevel),
                'difficulty_code' => (int) $tutorial->DifficultyLevel,
                'tags' => $this->normalizeTags($tutorial->Tags),
                'content' => $tutorial->Content,
                'status' => $progress
                    ? ((int) $progress->IsCompleted === 1 ? 'completed' : 'in_progress')
                    : 'not_started',
                'is_completed' => $progress ? ((int) $progress->IsCompleted === 1) : false,
                'started_at' => $progress?->StartedAt,
                'completed_at' => $progress?->CompletedAt,
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $tutorials,
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $userId = Auth::id() ?: (int) $request->query('user_id', 0);

        $tutorial = Tutorial::findOrFail($id);

        $progress = null;
        if ($userId > 0) {
            $progress = TutorialProgress::where('UserID', $userId)
                ->where('TutorialID', $tutorial->ID)
                ->first();
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $tutorial->ID,
                'title' => $tutorial->Title,
                'difficulty' => $this->normalizeDifficulty($tutorial->DifficultyLevel),
                'difficulty_code' => (int) $tutorial->DifficultyLevel,
                'tags' => $this->normalizeTags($tutorial->Tags),
                'content' => $tutorial->Content,
                'status' => $progress
                    ? ((int) $progress->IsCompleted === 1 ? 'completed' : 'in_progress')
                    : 'not_started',
                'is_completed' => $progress ? ((int) $progress->IsCompleted === 1) : false,
                'started_at' => $progress?->StartedAt,
                'completed_at' => $progress?->CompletedAt,
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $userId = Auth::id() ?: (int) $request->input('user_id', 0);
        $tutorialId = (int) $request->input('tutorial_id', 0);

        if ($userId <= 0 || $tutorialId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Hiányzó user_id vagy tutorial_id.',
            ], 422);
        }

        Tutorial::findOrFail($tutorialId);

        $progress = TutorialProgress::firstOrCreate(
            [
                'UserID' => $userId,
                'TutorialID' => $tutorialId,
            ],
            [
                'IsCompleted' => 0,
                'StartedAt' => now(),
                'CompletedAt' => null,
            ]
        );

        if (!$progress->StartedAt) {
            $progress->StartedAt = now();
            $progress->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Tutorial elindítva.',
            'data' => [
                'tutorial_id' => $tutorialId,
                'status' => ((int) $progress->IsCompleted === 1) ? 'completed' : 'in_progress',
                'started_at' => $progress->StartedAt,
                'completed_at' => $progress->CompletedAt,
            ],
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $userId = Auth::id() ?: (int) $request->input('user_id', 0);
        $tutorialId = (int) $request->input('tutorial_id', 0);

        if ($userId <= 0 || $tutorialId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Hiányzó user_id vagy tutorial_id.',
            ], 422);
        }

        Tutorial::findOrFail($tutorialId);

        $progress = TutorialProgress::firstOrCreate(
            [
                'UserID' => $userId,
                'TutorialID' => $tutorialId,
            ],
            [
                'IsCompleted' => 1,
                'StartedAt' => now(),
                'CompletedAt' => now(),
            ]
        );

        $progress->IsCompleted = 1;
        $progress->StartedAt = $progress->StartedAt ?: now();
        $progress->CompletedAt = now();
        $progress->save();

        return response()->json([
            'ok' => true,
            'message' => 'Tutorial befejezve.',
            'data' => [
                'tutorial_id' => $tutorialId,
                'status' => 'completed',
                'started_at' => $progress->StartedAt,
                'completed_at' => $progress->CompletedAt,
            ],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $userId = Auth::id() ?: (int) $request->query('user_id', 0);

        if ($userId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Hiányzó user_id.',
            ], 422);
        }

        $allCount = Tutorial::count();
        $completedCount = TutorialProgress::where('UserID', $userId)
            ->where('IsCompleted', 1)
            ->count();

        $beginnerTotal = Tutorial::where('DifficultyLevel', 1)->count();
        $advancedTotal = Tutorial::where('DifficultyLevel', 2)->count();
        $proTotal = Tutorial::where('DifficultyLevel', 3)->count();

        $beginnerCompleted = TutorialProgress::where('UserID', $userId)
            ->where('IsCompleted', 1)
            ->whereIn('TutorialID', function ($query) {
                $query->select('ID')
                    ->from('tutorials')
                    ->where('DifficultyLevel', 1);
            })
            ->count();

        $advancedCompleted = TutorialProgress::where('UserID', $userId)
            ->where('IsCompleted', 1)
            ->whereIn('TutorialID', function ($query) {
                $query->select('ID')
                    ->from('tutorials')
                    ->where('DifficultyLevel', 2);
            })
            ->count();

        $proCompleted = TutorialProgress::where('UserID', $userId)
            ->where('IsCompleted', 1)
            ->whereIn('TutorialID', function ($query) {
                $query->select('ID')
                    ->from('tutorials')
                    ->where('DifficultyLevel', 3);
            })
            ->count();

        return response()->json([
            'ok' => true,
            'data' => [
                'total' => $allCount,
                'completed' => $completedCount,
                'percent' => $allCount > 0 ? round(($completedCount / $allCount) * 100, 2) : 0,
                'levels' => [
                    'beginner' => [
                        'code' => 1,
                        'label' => 'Kezdő',
                        'total' => $beginnerTotal,
                        'completed' => $beginnerCompleted,
                        'percent' => $beginnerTotal > 0 ? round(($beginnerCompleted / $beginnerTotal) * 100, 2) : 0,
                    ],
                    'advanced' => [
                        'code' => 2,
                        'label' => 'Haladó',
                        'total' => $advancedTotal,
                        'completed' => $advancedCompleted,
                        'percent' => $advancedTotal > 0 ? round(($advancedCompleted / $advancedTotal) * 100, 2) : 0,
                    ],
                    'pro' => [
                        'code' => 3,
                        'label' => 'Profi',
                        'total' => $proTotal,
                        'completed' => $proCompleted,
                        'percent' => $proTotal > 0 ? round(($proCompleted / $proTotal) * 100, 2) : 0,
                    ],
                ],
            ],
        ]);
    }
}