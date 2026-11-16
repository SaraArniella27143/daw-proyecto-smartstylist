<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexScheduleRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Services\ScheduleService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends ApiController
{
    public function __construct(private readonly ScheduleService $scheduleService) {}

    public function index(IndexScheduleRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return ScheduleResource::collection(
            $this->scheduleService->listForUser(
                $this->authenticatedUser($request),
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            )
        )->response();
    }

    public function show(Request $request, int $schedule): ScheduleResource
    {
        return new ScheduleResource(
            $this->scheduleService->findForUser($this->authenticatedUser($request), $schedule)
        );
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $attributes = $request->validated();

        $schedule = $this->scheduleService->createForUser(
            $user,
            $attributes['outfit_id'],
            $attributes['scheduled_date'],
        );

        if ($schedule === null) {
            return ProblemDetails::response(409, 'Ya existe una planificación para esa fecha.');
        }

        return (new ScheduleResource($schedule))->response()->setStatusCode(201);
    }

    public function update(UpdateScheduleRequest $request, int $schedule): ScheduleResource|JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $attributes = $request->validated();

        $result = $this->scheduleService->updateForUser($user, $schedule, $attributes);

        if ($result === null) {
            return ProblemDetails::response(409, 'Ya existe una planificación para esa fecha.');
        }

        return new ScheduleResource($result);
    }

    public function destroy(Request $request, int $schedule): JsonResponse
    {
        $this->scheduleService->deleteForUser($this->authenticatedUser($request), $schedule);

        return response()->json(status: 204);
    }
}
