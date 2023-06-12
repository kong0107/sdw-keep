export default function showElapsedTime() {
    const start = Date.now();
    const intervalID = setInterval(() => {
        let message = '\relapsed time: ';
        let diff = Date.now() - start;

        const hours = Math.floor(diff / 3600000);
        if(hours > 0) {
            message += hours + ':';
            diff -= hours * 3600000;
        }

        const minutes = Math.floor(diff / 60000).toString().padStart(2, '0');
        message += minutes + ':';
        diff -= minutes * 60000;

        let seconds = Math.round(diff / 100) / 10;
        seconds = ((seconds < 10) ? '0' : '') + seconds.toString();
        if(seconds.length === 2) seconds += '.0';
        message += seconds + '\t';

        process.stdout.write(message);
    }, 40);

    return function() {
        clearInterval(intervalID);
        process.stdout.write('\n');
        return Date.now() - start;
    };
}
