<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainValidationException;

class ReservationBookingPolicyService
{
    private const RESTRICTED_VENUE_KEYWORDS = [
        'classroom',
        'class room',
        'avr',
        'case room',
        'caseroom',
    ];

    private const EXTENDED_WINDOW_KEYWORDS = [
        'rso',
        'registered student organization',
        'institutional',
        'institution event',
        'institutional event',
        'university event',
        'school-wide',
        'school wide',
    ];

    public function __construct(private readonly VenueRepository $venueRepository)
    {
    }

    public function validateReservationWindow(
        ReservationCreateRequestDTO $requestDTO,
        \DateTimeInterface $eventDateTime,
        \DateTimeInterface $endDateTime
    ): void {
        $today = new \DateTimeImmutable('today');
        $defaultWindowEnd = $this->resolveCurrentTermEnd($today);

        if ($eventDateTime <= $defaultWindowEnd && $endDateTime <= $defaultWindowEnd) {
            return;
        }

        if (!$this->qualifiesForExtendedWindow($requestDTO)) {
            throw new DomainValidationException(
                sprintf(
                    'Reservations are currently limited to the active booking period ending on %s unless the request is for an approved RSO or institutional event.',
                    $defaultWindowEnd->format('F j, Y')
                )
            );
        }

        if ($this->usesRestrictedVenue($requestDTO)) {
            throw new DomainValidationException(
                'Extended-term reservations are not allowed for classrooms, AVR rooms, or case rooms.'
            );
        }

        $extendedWindowEnd = $this->resolveNextTermEnd($today);
        if ($eventDateTime > $extendedWindowEnd || $endDateTime > $extendedWindowEnd) {
            throw new DomainValidationException(
                sprintf(
                    'Approved RSO or institutional events can only be scheduled through %s.',
                    $extendedWindowEnd->format('F j, Y')
                )
            );
        }
    }

    private function qualifiesForExtendedWindow(ReservationCreateRequestDTO $requestDTO): bool
    {
        $haystack = strtolower(trim(implode(' ', array_filter([
            $requestDTO->organizationName,
            $requestDTO->activityType,
            $requestDTO->purposeDescription,
        ]))));

        foreach (self::EXTENDED_WINDOW_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function usesRestrictedVenue(ReservationCreateRequestDTO $requestDTO): bool
    {
        if ($requestDTO->venueIdentifier === null) {
            return false;
        }

        $venue = $this->venueRepository->find($requestDTO->venueIdentifier);
        if ($venue === null) {
            return false;
        }

        $venueDescriptor = strtolower(trim(implode(' ', array_filter([
            $venue->getVenueName(),
            $venue->getVenueLocation(),
            $venue->getDescription(),
        ]))));

        foreach (self::RESTRICTED_VENUE_KEYWORDS as $keyword) {
            if (str_contains($venueDescriptor, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function resolveCurrentTermEnd(\DateTimeImmutable $today): \DateTimeImmutable
    {
        $month = (int)$today->format('n');
        $year = (int)$today->format('Y');

        if ($month >= 1 && $month <= 5) {
            return new \DateTimeImmutable(sprintf('%d-05-31 23:59:59', $year));
        }

        if ($month >= 6 && $month <= 7) {
            return new \DateTimeImmutable(sprintf('%d-07-31 23:59:59', $year));
        }

        return new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));
    }

    private function resolveNextTermEnd(\DateTimeImmutable $today): \DateTimeImmutable
    {
        $month = (int)$today->format('n');
        $year = (int)$today->format('Y');

        if ($month >= 1 && $month <= 5) {
            return new \DateTimeImmutable(sprintf('%d-07-31 23:59:59', $year));
        }

        if ($month >= 6 && $month <= 7) {
            return new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));
        }

        return new \DateTimeImmutable(sprintf('%d-05-31 23:59:59', $year + 1));
    }
}
