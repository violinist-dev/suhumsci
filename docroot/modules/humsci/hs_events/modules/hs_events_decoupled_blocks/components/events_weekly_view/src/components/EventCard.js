import {PropTypes} from 'react'

export const EventCard = ({title, isoEventDate, description, imageUrl}) => {
  let date = new Date(Date.parse(isoEventDate));
  return (
    <div className="event-card">
      <div className="decanter-width-three-fourths">
        <h2 className="title">
          {title}
        </h2>
        <div className="date">
          {(date.getUTCMonth() + 1) +'/' + date.getUTCDate() + '/' + date.getFullYear()}
        </div>
        <div className="description"
             dangerouslySetInnerHTML={{__html: description}}/>
      </div>

      <div className="image decanter-width-one-fourth">
        <img src={imageUrl} width={200}/>
      </div>
    </div>
  )
};

EventCard.propTypes = {
  title: PropTypes.string,
  description: PropTypes.string,
  imageUrl: PropTypes.string
};
