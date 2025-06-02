<?php
interface AccountInterface
{
    public function deposit(int $amount): void;
    public function withdraw(int $amount): void;
    public function transferTo(Account $target, int $amount): void;
    public function getBalance(): int;
}
?>