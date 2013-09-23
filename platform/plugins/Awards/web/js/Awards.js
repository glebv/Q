Q.Awards = Q.plugins.Awards = {};

(function($, Awards, Streams) {

	Awards.onCredits = new Q.Event();
	
	Streams.onMessage('Awards/credits', "").set(function (data) {
		
		// TODO: nazar, implement this and trigger the following event:
		// Q.plugins.Awards.amount = amount;
		// Awards.onCredits.handle(amount);
		// Dima can then update the dashboard when the amount changes etc.
		
	});
	
	Q.onReady.set(function () {
		Awards.onCredits.handle(Q.plugins.Awards.credits.amount);
	}, 'Awards');

})(jQuery, Q.plugins.Awards, Q.plugins.Streams);