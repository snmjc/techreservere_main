<?php

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\DTO\ReservationCreateRequestDTO;
use App\Domain\Venue\Repository\VenueRepository;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\AppClock;

class ReservationBookingPolicyService
{
    public function __construct(
        private readonly VenueRepository $venueRepository,
        private readonly ReservationPolicyConfigService $reservationPolicyConfigService
    )
    {
    }

    public function validateReservationWindow(
        ReservationCreateRequestDTO $requestDTO,
        \DateTimeInterface $eventDateTime,
        \DateTimeInterface $endDateTime
    ): void {
        $bookingWindow = $this->reservationPolicyConfigService->getBookingWindow();
        $activeWindowStart = new \DateTimeImmutable(
            sprintf('%s 00:00:00', $bookingWindow['activeBookingStartDate']),
            AppClock::timezone()
        );
        $activeWindowEnd = new \DateTimeImmutable(
            sprintf('%s 23:59:59', $bookingWindow['activeBookingEndDate']),
            AppClock::timezone()
        );
        $extendedWindowEnd = new \DateTimeImmutable(
            sprintf('%s 23:59:59', $bookingWindow['extendedBookingEndDate']),
            AppClock::timezone()
        );
        $eventStart = \DateTimeImmutable::createFromInterface($eventDateTime)->setTimezone(AppClock::timezone());
        $eventEnd = \DateTimeImmutable::createFromInterface($endDateTime)->setTimezone(AppClock::timezone());

        if ($eventStart < $activeWindowStart) {
            throw new DomainValidationException(
                sprintf(
                    'Reservations can only be made within the active booking window from %s to %s.',
                    $activeWindowStart->format('F j, Y'),
                    $activeWindowEnd->format('F j, Y')
                ),
                'ReservationBookingWindowNotOpen'
            );
        }

        if ($eventStart <= $activeWindowEnd && $eventEnd <= $activeWindowEnd) {
            return;
        }

        if (
            !$this->allowsExemptionOverride($bookingWindow)
            || !$this->qualifiesForExtendedWindow($requestDTO, $bookingWindow)
        ) {
            throw new DomainValidationException(
                sprintf(
                    'Reservations are currently limited to the active booking window from %s to %s.',
                    $activeWindowStart->format('F j, Y'),
                    $activeWindowEnd->format('F j, Y')
                ),
                'ReservationBookingWindowExceeded'
            );
        }

        if ($this->usesRestrictedVenue($requestDTO, $bookingWindow)) {
            throw new DomainValidationException(
                'Extended-term reservations are not allowed for classrooms, AVR rooms, or case rooms.',
                'ReservationRestrictedVenueForExtendedWindow'
            );
        }

        if ($eventStart > $extendedWindowEnd || $eventEnd > $extendedWindowEnd) {
            throw new DomainValidationException(
                sprintf(
                    'Approved exempt events can only be scheduled through %s.',
                    $extendedWindowEnd->format('F j, Y')
                ),
                'ReservationExtendedWindowExceeded'
            );
        }
    }

    private function allowsExemptionOverride(array $bookingWindow): bool
    {
        return (bool) ($bookingWindow['allowExemptions'] ?? true);
    }

    private function qualifiesForExtendedWindow(ReservationCreateRequestDTO $requestDTO, array $bookingWindow): bool
    {
        $haystack = strtolower(trim(implode(' ', array_filter([
            $requestDTO->organizationName,
            $requestDTO->activityType,
            $requestDTO->purposeDescription,
        ]))));

        $keywords = is_array($bookingWindow['exemptionKeywords'] ?? null)
            ? $bookingWindow['exemptionKeywords']
            : [];

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function usesRestrictedVenue(ReservationCreateRequestDTO $requestDTO, array $bookingWindow): bool
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
            $venue->getFloorLevel(),
            $venue->getDescription(),
        ]))));

        $keywords = is_array($bookingWindow['restrictedVenueKeywords'] ?? null)
            ? $bookingWindow['restrictedVenueKeywords']
            : [];

        foreach ($keywords as $keyword) {
            if (str_contains($venueDescriptor, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
