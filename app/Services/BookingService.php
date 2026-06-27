<?php
namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingCheckedOut;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            //lock the resource for update to prevent
            $resource = Resource::lockForUpdate()->findOrFail($data['resource_id']);
            //check overlap
            $exists = Booking::lockForUpdate()->where('resource_id',$resource->id)
                ->whereIn('status',[
                    BookingStatus::PENDING,
                    BookingStatus::CONFIRMED
                ])
                ->where(function($q) use ($data){
                    $q->whereBetween('starts_at',[
                        $data['starts_at'],
                        $data['ends_at']
                    ])
                    ->orWhereBetween('ends_at',[
                        $data['starts_at'],
                        $data['ends_at']
                    ])
                    ->orWhere(function($q) use ($data){
                        $q->where('starts_at','<=',$data['starts_at'])
                          ->where('ends_at','>=',$data['ends_at']);
                    });
                })->exists();
            if($exists){
                throw new \Exception('Resource already booked.');
            }
            return Booking::create($data + [
                'resource_type'=>$resource->type,
                'status'=>BookingStatus::CONFIRMED
            ]);

        });
    }
    public function checkout($booking_ref): Booking
    {
        $booking = Booking::where('booking_ref', $booking_ref)->first();
        if (!$booking) {
            throw new \Exception('Booking not found.');
        }
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status'=>BookingStatus::CHECKED_OUT,
                'checked_out_at'=>now(),
            ]);
            event(new BookingCheckedOut($booking));
            return $booking;
        });
    }
}
