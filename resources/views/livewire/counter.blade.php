<div>
    <h1>Counter: {{ $count }}</h1>
    <button wire:click="increment">Increment</button>
    <button wire:click="decrement">Decrement</button>
    <button wire:click="resetValue">Reset</button>
    <button wire:click="incrementByAmount(5)">Increment by 5</button>
</div>
