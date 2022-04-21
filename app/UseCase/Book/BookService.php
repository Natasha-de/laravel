<?php
namespace App\UseCase\Book;

use App\Events\BookUpdatePrice;
use App\Exceptions\BusinessException;
use App\Mail\NewBook;
use App\Models\Book;
use App\Dto\BookDto;
use App\Services\Sms\Sms;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Mail;

class BookService
{

    public function __construct(
        public Mailer $mailer,
        public Dispatcher $dispatcher,
        public ConnectionInterface $connection,
        public Sms $sms,
    ) {
    }

    public function create(BookDto $bookDto, array $authors_ids): Book
    {
        $this->isbnIsUnique($bookDto->isbn);

        \DB::beginTransaction();

        $book = new Book;
        $book->isbn = $bookDto->isbn;
        $book->title = $bookDto->title;
        $book->price = $bookDto->price;
        $book->page = $bookDto->page;
        $book->year = $bookDto->year;
        $book->excerpt = $bookDto->excerpt;
        $book->save();

        $book->authors()->sync($authors_ids);

        \DB::commit();


//        $book->authors()->attach($authors_ids);

//        detach


        return $book;
    }


    public function notifyBook($book)
    {
        $this->mailer->send(new NewBook($book));
//        Mail::send(new NewBook($book));


        $this->sms->send('+7999999999', 'New Book Created');
    }



    public function update(int $id, array $authors_ids, BookDto $bookDto): Book
    {
        $book = Book::findOrFail($id);

        $this->isbnIsUnique($bookDto->isbn, $id);

        $old_price = $book->price;

        $this->connection->transaction(function() use ($book, $bookDto, $authors_ids) {
//        \DB::transaction(function () use($book, $bookDto, $authors_ids) {
            $book->update([
                'isbn' => $bookDto->isbn,
                'title' => $bookDto->title,
                'price' => $bookDto->price,
                'year' => $bookDto->year,
                'page' => $bookDto->page,
                'excerpt' => $bookDto->excerpt,
            ]);
            $book->authors()->sync($authors_ids);
        });



        if($bookDto->price < $old_price){
            $this->dispatcher->dispatch(
                new BookUpdatePrice($book->id)
            );
        }
        return $book;
    }

    private function isbnIsUnique(string $isbn, $exceptId = null): void
    {
        if($exceptId){
            $existBook = Book::where('isbn', $isbn)->where('id', '<>', $exceptId)->count();
        }else{
            $existBook = Book::where('isbn', $isbn)->count();
        }

        if($existBook){
            throw new BusinessException("Book with this isbn already exists " . $isbn);
        }
    }
}
