import {Component} from 'react'
import {EventList} from "./EventList";

export class App extends Component {

  constructor(props) {
    super(props);

    this.state = {events: []};

    fetch('//docroot.suhumsci.loc/api/hs_event?_format=json')
      .then(results => {
        return results.json();
      }).then(data => {

      this.setState({
        events: this.sortEvents(data.Event)
      })
    })
  }

  sortEvents = (events, sortField = "isoEventDate") => {
    events.sort(function (a, b) {
      try {
        let aDate = new Date.parse(a[sortField]);
        let bDate = new Date.parse(a[sortField]);
        return aDate - bDate;
      }
      catch (e) {
        // Do nothing.
      }

      let tempArray = [a[sortField], b[sortField]];
      tempArray.sort();
      return tempArray[0] === a[sortField] ? -1 : 1;
    });
    return events;
  };

  render() {
    return (
      (this.state.events.length ?
          <div className="app">
            <EventList events={this.state.events}/>
          </div>
          :
          <div className="app">
            <div className="loading">
              Loading...
            </div>
          </div>
      )
    )
  }
}
